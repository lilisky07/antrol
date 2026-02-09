// AmbilModal.js
import React from 'react';
import Button from './atoms/button';
import Input from './atoms/input';
import Card from './atoms/card';

function AmbilModal({
  show,
  setShow,
  selectedItem,
  selectedTanggal,
  setSelectedTanggal,
  ambilLoading,
  ambilSuccess,
  confirmAmbil,
  sisaKuota,            
  cekKuotaLoading      
}) {
  if (!show || !selectedItem) return null;

  return (
    <div
      style={{
        position: 'fixed',
        inset: 0,
        backgroundColor: 'rgba(0, 0, 0, 0.65)',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        zIndex: 9999,
        overflow: 'auto',
      }}
      onClick={(e) => {
        if (e.target === e.currentTarget) setShow(false);
      }}
    >
      <Card
        style={{
          maxWidth: '540px',
          width: '92%',
          padding: '28px',
          borderRadius: '16px',
          backgroundColor: 'white',
          boxShadow: '0 20px 40px rgba(0,0,0,0.4)',
          position: 'relative',
          maxHeight: '90vh',
          overflowY: 'auto',
        }}
        onClick={(e) => e.stopPropagation()}
      >
        <h3 style={{ color: '#16a34a', margin: '0 0 24px', fontSize: '24px', fontWeight: 600 }}>
          Konfirmasi Ambil Antrean
        </h3>

        <div style={{ marginBottom: '24px', lineHeight: 1.6, fontSize: '15px' }}>
          Pasien: <strong>{selectedItem.nama}</strong><br />
          No RM: <strong>{selectedItem.no_rm}</strong><br />
          No Surat: <strong>{selectedItem.no_surat}</strong><br />
          Poli: <strong>{selectedItem.poli || selectedItem.nm_poli_bpjs || '-'}</strong><br />
          Dokter: <strong>{selectedItem.dokter || selectedItem.nm_dokter_bpjs || '-'}</strong><br />
          Tgl Rencana Asli: <strong>{selectedItem.tgl_rencana || '-'}</strong>
        </div>

        <div style={{ marginBottom: '24px' }}>
          <label style={{ display: 'block', marginBottom: '8px', fontWeight: 500 }}>
            Pilih Tanggal Antrean
          </label>
          <Input
            type="date"
            value={selectedTanggal}
            onChange={(e) => setSelectedTanggal(e.target.value)}
            min={new Date().toISOString().split('T')[0]}
            onClick={(e) => e.stopPropagation()}
            onMouseDown={(e) => e.stopPropagation()}
            onFocus={(e) => e.stopPropagation()}
            onKeyDown={(e) => e.stopPropagation()}
            style={{ width: '100%', padding: '12px', fontSize: '16px' }}
          />
        </div>

        {/* 🔥 SISA KUOTA DARI ENDPOINT */}
        <div
          style={{
            padding: '16px',
            background: sisaKuota <= 5 ? '#fee2e2' : '#ecfdf5',
            borderRadius: '12px',
            textAlign: 'center',
            marginBottom: '28px',
            fontSize: '18px',
            fontWeight: 600
          }}
        >
          {cekKuotaLoading ? (
            'Cek kuota...'
          ) : (
            <>
              Sisa Kuota: {sisaKuota ?? 0}
              {sisaKuota <= 5 && sisaKuota > 0 && (
                <div style={{ fontSize: '14px', marginTop: '8px', color: '#dc2626' }}>
                  ⚠️ Kuota hampir habis!
                </div>
              )}
            </>
          )}
        </div>

        {ambilSuccess ? (
          <div
            style={{
              padding: '20px',
              background: '#ecfdf5',
              borderRadius: '12px',
              textAlign: 'center',
              color: '#065f46',
              marginBottom: '20px'
            }}
          >
            <strong style={{ fontSize: '20px' }}>✓ Berhasil diambil!</strong><br />
            <p style={{ marginTop: '12px' }}>List antrean telah di-refresh.</p>
          </div>
        ) : (
          <div style={{ display: 'flex', gap: '16px', justifyContent: 'flex-end' }}>
            <Button variant="outline" onClick={() => setShow(false)}>
              Batal
            </Button>
            <Button
              variant="success"
              onClick={confirmAmbil}
              disabled={ambilLoading || !selectedTanggal || sisaKuota <= 0}
            >
              {ambilLoading ? 'Memproses...' : 'Ambil Antrean'}
            </Button>
          </div>
        )}
      </Card>

      {/* Animasi fade-in */}
      <style jsx>{`
        div[style*="position: fixed"] {
          animation: fadeIn 0.3s ease-out;
        }
        @keyframes fadeIn {
          from { opacity: 0; }
          to { opacity: 1; }
        }
      `}</style>
    </div>
  );
}

export default AmbilModal;
