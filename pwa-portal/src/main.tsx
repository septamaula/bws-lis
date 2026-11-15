import React from 'react';
import { createRoot } from 'react-dom/client';
import { registerSW } from 'virtual:pwa-register';
import App from './App';
import './index.css';

registerSW({
  onNeedRefresh() {
    console.log('[BWS ELAPSE] Versi baru tersedia.');
  },
  onOfflineReady() {
    console.log('[BWS ELAPSE] Aplikasi siap diakses secara offline.');
  },
});

createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);

