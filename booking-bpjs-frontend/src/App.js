import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import Home from './components/pages/home';
import MainPage from './components/mainpage';

function App() {
  return (
    <Router>
      <Routes>
        {/* Langsung redirect ke dashboard kalau akses root / */}
        <Route path="/" element={<Navigate to="/dashboard" replace />} />

        {/* Route dashboard */}
        <Route path="/dashboard" element={<MainPage />} />

        {/* Optional: Kalau mau home tetap ada (misal /home), bisa uncomment */}
        {/* <Route path="/home" element={<Home />} /> */}

        {/* Catch all route (kalau ketik URL lain) langsung ke dashboard */}
        <Route path="*" element={<Navigate to="/dashboard" replace />} />
      </Routes>
    </Router>
  );
}

export default App;