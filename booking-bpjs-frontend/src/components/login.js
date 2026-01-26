import React, { useState } from 'react';
import axios from 'axios';
import Button from './atoms/button';
import Input from './atoms/input';
import Card from './atoms/card';

function Login() {
  const [nip, setNip] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleLogin = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      const response = await axios.post('http://127.0.0.1:8000/api/login', { nip: nip.trim() });
      if (response.data.success) {
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('petugas', JSON.stringify(response.data.petugas));
        window.location.href = '/';
      }
    } catch (err) {
      setError('NIP petugas tidak ditemukan. Coba lagi.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{
      minHeight: '100vh',
      background: 'linear-gradient(135deg, #f0f9ff, #e0f2fe)',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      padding: '20px',
      fontFamily: 'Segoe UI, sans-serif'
    }}>
      <Card>
        <div style={{textAlign:'center', marginBottom:'30px'}}>
          <h1 style={{fontSize:'28px', fontWeight:'700', color:'#1e40af', margin:'0 0 8px'}}>Booking Antrean</h1>
          <h2 style={{fontSize:'24px', fontWeight:'600', color:'#2563eb', margin:0}}>BPJS Kesehatan</h2>
          <p style={{color:'#64748b', fontSize:'14px', marginTop:'10px'}}>Aplikasi Online MJKN</p>
        </div>

     <form onSubmit={handleLogin} style={{textAlign: 'center'}}>
          <div style={{marginBottom: '2'}}>
            <label style={{
              display: 'block',
              fontSize: '15px',
              fontWeight: '500',
              color: '#374151',
              marginBottom: '8px'
            }}>
              ID Petugas / NIP
            </label>
            <Input placeholder="Masukkan NIP petugas" value={nip} onChange={(e) => setNip(e.target.value)} required />
          </div>

          {error && (
            <div style={{
              background:'#fee2e2',
              color:'#dc2626',
              padding:'12px 16px',
              borderRadius:'10px',
              textAlign:'center',
              margin:'20px 0',
              fontSize:'14px',
              border:'1px solid #fecaca'
            }}>
              {error}
            </div>
          )}

          <div style={{marginTop: '10px'}}>
            <Button variant="primary" disabled={loading}>
              {loading ? 'Memproses...' : 'Login'}
            </Button>
          </div>
        </form>

        <div style={{textAlign:'center', marginTop:'30px', color:'#94a3b8', fontSize:'13px'}}>
          Untuk testing gunakan NIP: <span style={{fontWeight:'bold', color:'#2563eb', fontFamily:'monospace'}}>123456</span>
        </div>
        <div style={{textAlign:'center', marginTop:'30px', color:'#cbd5e1', fontSize:'12px'}}>
          © BPJS Kesehatan - Mobile JKN
        </div>
      </Card>
    </div>
  );
}

export default Login;