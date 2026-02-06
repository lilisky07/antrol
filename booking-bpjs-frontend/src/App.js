import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import Home from './components/pages/home';
import MainPage from './components/mainpage';

function App() {
  return (
    <Router>
      <Routes>
        {/* Langsung redirect ke dashboard kalau akses root / */}
        <Route path="/" element={<Navigate to="/bookingbpjs" replace />} />

        {/* Route dashboard */}
        <Route path="/bookingbpjs" element={<MainPage />} />

        {/* Optional: Kalau mau home tetap ada (misal /home), bisa uncomment */}
        {/* <Route path="/home" element={<Home />} /> */}

        {/* Catch all route (kalau ketik URL lain) langsung ke dashboard */}
        <Route path="*" element={<Navigate to="/bookingbpjs" replace />} />
      </Routes>
    </Router>
  );
}

export default App;