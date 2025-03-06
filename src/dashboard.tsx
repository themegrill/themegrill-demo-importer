import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';
import './assets/css/dashboard.pcss';

const root = createRoot(document.getElementById('tg-demo-importer')!);
root.render(<App />);
