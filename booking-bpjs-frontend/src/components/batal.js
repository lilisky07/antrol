// BatalModal.js
import React from 'react';
import Button from './atoms/button';
import Card from './atoms/card';

function BatalModal({ show, setShow, selectedBatal, batalLoading, confirmBatal }) {
  if (!show || !selectedBatal) return null;

  return (
    <div
      className="modal-overlay"
      onClick={(e) => {
        if (e.target === e.currentTarget) setShow(false);
      }}
    >
      <Card style={{ maxWidth: '500px', width: '90%', padding: '28px' }} onClick={e => e.stopPropagation()}>
        <h3 style={{ color: '#dc2626', margin: '0 0 24px', fontSize: '24px' }}>
          Konfirmasi Batal Antrean
        </h3>
        <p style={{ marginBottom: '28px', lineHeight: 1.6 }}>
          Yakin membatalkan antrean untuk <strong>{selectedBatal.nama}</strong>?<br />
          No RM: <strong>{selectedBatal.no_rm}</strong><br />
          No Surat: <strong>{selectedBatal.no_surat}</strong>
        </p>

        <div style={{ display: 'flex', gap: '16px', justifyContent: 'flex-end' }}>
          <Button variant="outline" onClick={() => setShow(false)}>
            Tidak
          </Button>
          <Button variant="danger" onClick={confirmBatal} disabled={batalLoading}>
            {batalLoading ? 'Memproses...' : 'Ya, Batalkan'}
          </Button>
        </div>
      </Card>
    </div>
  );
}

export default BatalModal;