import React from 'react';
import { Link } from 'react-router-dom';
import Button from '../atoms/button';
import Input from '../atoms/input';
import Card from '../atoms/card';
import LayananCard from '../atoms/LayananCard';
import Navbar from '../molecules/Navbar';
export default function Home() {
  const layanan = [
    { icon: '🔍', title: 'Cari Dokter' },
    { icon: '🏥', title: 'Layanan Unggulan' },
    { icon: '🩺', title: 'Medical Check Up' },
    { icon: '🏠', title: 'Gladish at Home' },
    { icon: '🧪', title: 'Lab' },
    { icon: '📡', title: 'Radiologi' },
  ];

  return (
   <div style={{ minHeight: '100vh', backgroundColor: '#f8fafc', fontFamily: 'Segoe UI, sans-serif' }}>
      
      <Navbar />

      <div style={{ height: '30px' }}></div>

      {/* Hero Section */}
      <div style={{ padding: '60px 0 80px', background: 'linear-gradient(to bottom, #eff6ff, #f8fafc)' }}>
        <div style={{ maxWidth: '1600px', margin: '0 auto', padding: '0 50px', display: 'flex', alignItems: 'flex-start', justifyContent: 'space-between', gap: '80px' }}>
          {/* Kiri - Tulisan & Form */}
          <div style={{ flex: 1, maxWidth: '580px', marginTop: '10px' }}>
            <p style={{ fontSize: '18px', color: '#64748b', marginBottom: '10px' }}>
              Selamat Datang di RS Gladish Medical Centre
            </p>
            <h1 style={{ fontSize: '42px', fontWeight: '700', color: '#1e293b', lineHeight: '1.2', marginBottom: '30px' }}>
              Kesehatan Anda,<br />
              Prioritas Kami.
            </h1>

            <Card style={{ padding: '28px', borderRadius: '20px', boxShadow: '0 12px 30px rgba(0,0,0,0.1)' }}>
              <div style={{ display: 'flex', gap: '10px', marginBottom: '16px', flexWrap: 'wrap' }}>
                <Button variant="success" style={{ padding: '10px 24px', borderRadius: '50px', fontSize: '15px' }}>
                  Semua
                </Button>
                <Button variant="primary" style={{ padding: '10px 24px', borderRadius: '50px', fontSize: '15px' }}>
                  Dokter
                </Button>
                <Button variant="primary" style={{ padding: '10px 24px', borderRadius: '50px', fontSize: '15px' }}>
                  Lokasi
                </Button>
              </div>
              <Input placeholder="Ketik kata kunci (nama dokter, penyakit, layanan...)" />
            </Card>
          </div>

          {/* Kanan - Badge */}
          <div style={{ flex: 1, textAlign: 'center', marginTop: '50px' }}>
            <Card style={{ 
              display: 'inline-block', 
              padding: '40px', 
              borderRadius: '28px', 
              background: 'linear-gradient(to bottom right, #1e40af, #1e3a8a)',
              boxShadow: '0 18px 35px rgba(30,64,175,0.3)'
            }}>
              <div style={{ background: 'white', padding: '35px', borderRadius: '20px' }}>
                <h3 style={{ fontSize: '28px', fontWeight: '700', color: '#1e40af', marginBottom: '14px' }}>
                  Rumah Sakit Terbaik<br />
                  di Kota Anda 2026
                </h3>
                <p style={{ fontSize: '18px', color: '#1e40af' }}>
                  Peringkat #1 Pelayanan Kesehatan<br />
                  oleh Kementerian Kesehatan RI
                </p>
              </div>
            </Card>
          </div>
        </div>
      </div>

      {/* Layanan Unggulan - Horizontal Kecil & Naik Atas */}
    <div style={{ padding: '-20px 0', backgroundColor: '#f8fafc' }}>
  <div style={{ maxWidth: '1500px', margin: '0 auto', padding: '0 20px' }}>
    <div style={{ display: 'flex', gap: '20px', overflowX: 'auto', padding: '-10px 0' }}>
      {layanan.map((item, index) => (
        <LayananCard key={index} icon={item.icon} title={item.title} />
      ))}
    </div>
  </div>
</div>

      {/* Chat Button */}
      <div style={{ position: 'fixed', bottom: '40px', right: '40px', zIndex: 1000 }}>
        <Button variant="success" size="lg" style={{ boxShadow: '0 15px 30px rgba(22,163,74,0.4)' }}>
          💬 Let's Chat!
        </Button>
      </div>
    </div>
  );
}