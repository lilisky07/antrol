// AntreanTable.js
import React from 'react';
import Button from './atoms/button';
import Card from './atoms/card';

function AntreanTable({
  antrean,
  loading,
  errorMsg,
  currentPage,
  totalPages,
  setCurrentPage,
  handleAmbil,
  handleBatal
}) {
  const perPage = 20;

  // Urutkan data berdasarkan tanggal terbaru
  const sortedAntrean = React.useMemo(() => {
    return [...antrean].sort((a, b) => {
      const dateA = new Date(a.tgl_rencana);
      const dateB = new Date(b.tgl_rencana);
      return dateB - dateA; // Descending (terbaru dulu)
    });
  }, [antrean]);

  return (
    <Card fullWidth>
      <div style={{ padding: '24px' }}>
        <h2 style={{ margin: '0 0 20px', color: '#1d4ed8' }}>
          Daftar Rencana Kontrol BPJS
        </h2>

        {loading ? (
          <div style={{ textAlign: 'center', padding: '100px 0' }}>
            <div style={{
              border: '6px solid #f3f3f3',
              borderTop: '6px solid #3b82f6',
              borderRadius: '50%',
              width: '60px',
              height: '60px',
              animation: 'spin 1s linear infinite',
              margin: '0 auto'
            }} />
            <p style={{ marginTop: '20px', color: '#6b7280', fontSize: '18px' }}>
              Memuat data antrean...
            </p>
          </div>
        ) : errorMsg ? (
          <div style={{ textAlign: 'center', padding: '80px 0', color: '#dc2626', fontSize: '18px' }}>
            {errorMsg}
          </div>
        ) : antrean.length === 0 ? (
          <div style={{ textAlign: 'center', padding: '80px 0', color: '#6b7280', fontSize: '18px' }}>
            Tidak ada data ditemukan<br />
            <small style={{ display: 'block', marginTop: '12px' }}>
              Coba ubah filter atau tunggu surat kontrol baru
            </small>
          </div>
        ) : (
          <>
            <div style={{ overflowX: 'auto' }}>
              <table style={{ width: '100%', borderCollapse: 'collapse', minWidth: '900px' }}>
                <thead>
                  <tr style={{ background: '#eff6ff' }}>
                    <th style={{ padding: '14px', textAlign: 'left' }}>No RM</th>
                    <th style={{ padding: '14px', textAlign: 'left' }}>No Surat</th>
                    <th style={{ padding: '14px', textAlign: 'left' }}>Pasien</th>
                    <th style={{ padding: '14px', textAlign: 'left' }}>Tgl Rencana</th>
                    <th style={{ padding: '14px', textAlign: 'left' }}>Poli</th>
                    <th style={{ padding: '14px', textAlign: 'left' }}>Dokter</th>
                    <th style={{ padding: '14px', textAlign: 'left' }}>Status</th>
                    <th style={{ padding: '14px', textAlign: 'left' }}>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  {sortedAntrean.map(item => (
                    <tr key={item.no_surat} style={{ borderBottom: '1px solid #e5e7eb' }}>
                      <td style={{ padding: '14px' }}>{item.no_rm || '-'}</td>
                      <td style={{ padding: '14px', fontWeight: 600 }}>{item.no_surat || '-'}</td>
                      <td style={{ padding: '14px' }}>{item.nama || '-'}</td>
                      <td style={{ padding: '14px' }}>{item.tgl_rencana || '-'}</td>
                      <td style={{ padding: '14px' }}>{item.poli || item.nm_poli_bpjs || '-'}</td>
                      <td style={{ padding: '14px' }}>{item.dokter || item.nm_dokter_bpjs || '-'}</td>
                      <td style={{
                        padding: '14px',
                        fontWeight: 600,
                        color: item.status === 'Checkin' ? '#16a34a' : (item.status === 'Gagal' ? '#f97316' : (item.status === 'Batal' ? '#dc2626' : '#6b7280'))
                      }}>
                        {item.status || 'Belum'}
                      </td>
                      <td style={{ padding: '14px' }}>
                        {item.status === 'Checkin' ? (
                          <Button 
                            variant="danger" 
                            size="sm" 
                            onClick={() => handleBatal(item)}
                          >
                            Batal
                          </Button>
                        ) : (
                          <Button 
                            variant="success" 
                            size="sm" 
                            onClick={() => handleAmbil(item)}
                            disabled={item.status === 'Gagal' || item.status === 'Batal'}
                          >
                            Ambil
                          </Button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div style={{
              display: 'flex',
              justifyContent: 'center',
              alignItems: 'center',
              gap: '20px',
              marginTop: '32px',
              flexWrap: 'wrap'
            }}>
              <Button
                variant="outline"
                disabled={currentPage === 1 || loading}
                onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
              >
                ← Sebelumnya
              </Button>

              <span style={{ fontWeight: 600, color: '#1d4ed8', fontSize: '16px' }}>
                Halaman {currentPage} {totalPages > currentPage ? `/ ${totalPages}` : ''}
              </span>

              <Button
                variant="outline"
                disabled={loading || antrean.length < perPage}
                onClick={() => setCurrentPage(p => p + 1)}
              >
                Selanjutnya →
              </Button>
            </div>
          </>
        )}
      </div>
    </Card>
  );
}

export default AntreanTable;