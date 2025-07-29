import chalk from 'chalk';
import { spawn } from 'child_process';
import { dest, parallel, series, src } from 'gulp';
import _zip from 'gulp-zip';
import path from 'path';

function exec(command) {
	console.log(`Executing: ${command}`);

	return new Promise((resolve, reject) => {
		const parts = command.split(' ');
		const cmd = parts[0];
		const args = parts.slice(1);
		const process = spawn(cmd, args, {
			shell: true,
			stdio: 'pipe',
		});

		process.stdout.on('data', (data) => {
			console.log(chalk.green(data.toString().trim()));
		});

		process.stderr.on('data', (data) => {
			console.error(chalk.red(data.toString().trim()));
		});

		process.on('close', (code) => {
			if (code === 0) {
				console.log(chalk.green(`✓ Command completed successfully: ${command}`));
				resolve();
			} else {
				console.error(chalk.red(`✗ Command failed with exit code ${code}: ${command}`));
				reject(new Error(`Command failed with exit code ${code}`));
			}
		});

		process.on('error', (err) => {
			console.error(chalk.red(`✗ Failed to start command: ${command}`));
			console.error(chalk.red(err));
			reject(err);
		});
	});
}

const FILES = {
	'dist/**/*': 'build/themegrill-demo-importer/dist',
	'readme.txt': 'build/themegrill-demo-importer',
	'themegrill-demo-importer.php': 'build/themegrill-demo-importer',
	'composer.json': 'build/themegrill-demo-importer',
	'license.txt': 'build/themegrill-demo-importer',
	'package.json': 'build/themegrill-demo-importer',
	'includes/**/*': 'build/themegrill-demo-importer/includes',
	'languages/**/*': 'build/themegrill-demo-importer/languages',
};

const copyTasks = Object.entries(FILES).map(([source, destination]) => {
	const taskName = `copy:${path.basename(source.replace('/**/*', ''))}`;
	const copyTask = function () {
		return src(source).pipe(dest(destination));
	};
	copyTask.displayName = taskName;
	return copyTask;
});

const copy = parallel(...copyTasks);

const release = series(
	function clean() {
		return exec(`rm -rf release/ build/`);
	},
	function build() {
		return exec(`pnpm build`);
	},
	copy,
	function composer() {
		return exec(
			`cd build/themegrill-demo-importer && composer install --no-dev --optimize-autoloader`,
		);
	},
	function zip() {
		return src(['./build/**/*', '!./build/**/composer.json', '!./build/**/composer.lock'])
			.pipe(_zip('themegrill-demo-importer.zip'))
			.pipe(dest('release'));
	},
	function cleanBuild() {
		return exec(`rm -rf build/`);
	},
);

export { release };
