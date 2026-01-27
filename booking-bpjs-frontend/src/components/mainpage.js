import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import FilterSection from './filtersection';   
import AntreanTable from './antrean';
import AmbilModal from './ambil';
import BatalModal from './batal';
import Card from './atoms/card';
import Button from './atoms/button';
import Input from './atoms/input';

function MainPage() {
  // ── Semua state di sini ──
  const [antrean, setAntrean] = useState([]);
  const [loading, setLoading] = useState(true);
  const [errorMsg, setErrorMsg] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const perPage = 20;

  const [searchTerm, setSearchTerm] = useState('');
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const [selectedDate, setSelectedDate] = useState('');
  const [selectedPoli, setSelectedPoli] = useState('');
  const [selectedDokter, setSelectedDokter] = useState('');
  const [poliList, setPoliList] = useState([]);

  const [showAmbilModal, setShowAmbilModal] = useState(false);
  const [selectedItem, setSelectedItem] = useState(null);
  const [ambilLoading, setAmbilLoading] = useState(false);
  const [ambilSuccess, setAmbilSuccess] = useState(false);
  const [selectedTanggal, setSelectedTanggal] = useState('');

  const [showBatalModal, setShowBatalModal] = useState(false);
  const [selectedBatal, setSelectedBatal] = useState(null);
  const [batalLoading, setBatalLoading] = useState(false);

  // ── Fungsi handler ──
  const handleAmbil = (item) => {
    setSelectedItem(item);
    setSelectedTanggal(item.tgl_rencana || new Date().toISOString().split('T')[0]);
    setAmbilSuccess(false);
    setShowAmbilModal(true);
  };

  const confirmAmbil = async () => {
    if (!selectedItem || !selectedTanggal) return;
    setAmbilLoading(true);
    try {
      const res = await axios.post('http://127.0.0.1:8000/api/antrean/ambil', {
        no_rm: selectedItem.no_rm,
        no_surat: selectedItem.no_surat,
        kd_poli: selectedItem.kd_poli,
        kd_dokter: selectedItem.kd_dokter,
        tgl_antrean: selectedTanggal,
      });
      if (res.data.success) {
        setAmbilSuccess(true);
        fetchAntrean(); // refresh setelah sukses
      } else {
        alert(res.data.message || 'Gagal mengambil antrean');
      }
    } catch (err) {
      alert(err.response?.data?.message || 'Terjadi kesalahan');
    } finally {
      setAmbilLoading(false);
    }
  };

  const handleBatal = (item) => {
    setSelectedBatal(item);
    setShowBatalModal(true);
  };

  const confirmBatal = () => {
    setBatalLoading(true);
    setTimeout(() => {
      setAntrean(prev =>
        prev.map(i =>
          i.no_surat === selectedBatal.no_surat
            ? { ...i, status: 'Belum Booking', kode_booking: null, nomor_antrean: null }
            : i
        )
      );
      setBatalLoading(false);
      setShowBatalModal(false);
    }, 1200);
  };

  const resetFilters = () => {
    setSearchTerm('');
    setSelectedDate('');
    setSelectedPoli('');
    setSelectedDokter('');
    setCurrentPage(1);
  };
  
useEffect(() => {
  const fetchPoliList = async () => {
    try {
      const res = await axios.get('http://127.0.0.1:8000/api/antrean/poli-list');
      if (res.data.success) {
        const poliData = res.data.data || [];
        
        // Kalau data adalah array string langsung
        if (Array.isArray(poliData) && typeof poliData[0] === 'string') {
          setPoliList([...new Set(poliData)].sort()); // unique & urut alfabet
        } 
        // Kalau data adalah array object (contoh: [{nm_poli: "Poli Anak"}, ...])
        else {
          const poliNames = poliData
            .map(item => item.nm_poli || item.nm_poli_bpjs || item.nama_poli || '')
            .filter(Boolean);
          setPoliList([...new Set(poliNames)].sort());
        }
      } else {
        console.warn('Gagal fetch poli-list:', res.data.message);
      }
    } catch (err) {
      console.error('Error fetch poli-list:', err);
      // Optional: set error atau fallback ke empty array
    }
  };

  fetchPoliList();
}, []); // [] = hanya sekali saat mount


  // ── Debounce search ──
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearch(searchTerm);
      setCurrentPage(1);
    }, 600);
    return () => clearTimeout(timer);
  }, [searchTerm]);

  // ── INI BAGIAN YANG PENTING: Fetch antrean public-list ──
  const fetchAntrean = useCallback(async () => {
    setLoading(true);
    setErrorMsg('');

    try {
      const res = await axios.get('http://127.0.0.1:8000/api/antrean/public-list', {
        params: {
          page: currentPage,
          per_page: perPage,
          search: debouncedSearch || undefined,
          tgl_rencana: selectedDate || undefined,
          poli: selectedPoli || undefined,
          dokter: selectedDokter || undefined,
        }
      });

      if (res.data.success) {
        const data = res.data.data || [];
        setAntrean(data);

        // Set total pages (prioritas dari backend, fallback kalau simplePaginate)
        if (res.data.last_page) {
          setTotalPages(res.data.last_page);
        } else {
          setTotalPages(data.length === perPage ? currentPage + 1 : currentPage);
        }
      } else {
        setErrorMsg(res.data.message || 'Gagal memuat data antrean');
      }
    } catch (err) {
      console.error('Fetch antrean error:', err);
      setErrorMsg('Gagal terhubung ke server. Cek backend Laravel.');
    } finally {
      setLoading(false);
    }
  }, [currentPage, debouncedSearch, selectedDate, selectedPoli, selectedDokter]);

  // Panggil fetch saat komponen mount atau filter berubah
  useEffect(() => {
    fetchAntrean();
  }, [fetchAntrean]);

  return (
    <div style={{ minHeight: '100vh', background: 'linear-gradient(135deg, #f0f7ff, #e0f2fe)', padding: '16px 20px' }}>
      <div style={{ maxWidth: '1600px', margin: '0 auto' }}>

        {/* Header */}
        <Card fullWidth>
          <div style={{ padding: '24px', textAlign: 'left' }}>
            <h1 style={{ margin: 0, fontSize: 'clamp(26px, 5vw, 36px)', color: '#1d4ed8' }}>
              Booking Antrean BPJS Online
            </h1>
            <p style={{ color: '#64748b', marginTop: '8px', fontSize: '18px' }}>
              RS Gladish Medical Centre - Sistem Petugas
            </p>
          </div>
        </Card>

        {/* Filter */}
        <FilterSection
          searchTerm={searchTerm}
          setSearchTerm={setSearchTerm}
          selectedDate={selectedDate}
          setSelectedDate={setSelectedDate}
          selectedPoli={selectedPoli}
          setSelectedPoli={setSelectedPoli}
          selectedDokter={selectedDokter}
          setSelectedDokter={setSelectedDokter}
          poliList={poliList}
          resetFilters={resetFilters}
          setCurrentPage={setCurrentPage}
        />

        {/* Table + Pagination */}
        <AntreanTable
          antrean={antrean}
          loading={loading}
          errorMsg={errorMsg}
          currentPage={currentPage}
          totalPages={totalPages}
          setCurrentPage={setCurrentPage}
          handleAmbil={handleAmbil}
          handleBatal={handleBatal}
        />

        {/* Modal Ambil */}
        <AmbilModal
          show={showAmbilModal}
          setShow={setShowAmbilModal}
          selectedItem={selectedItem}
          selectedTanggal={selectedTanggal}
          setSelectedTanggal={setSelectedTanggal}
          ambilLoading={ambilLoading}
          ambilSuccess={ambilSuccess}
          confirmAmbil={confirmAmbil}
        />

        {/* Modal Batal */}
        <BatalModal
          show={showBatalModal}
          setShow={setShowBatalModal}
          selectedBatal={selectedBatal}
          batalLoading={batalLoading}
          confirmBatal={confirmBatal}
        />
      </div>
    </div>
  );
}

export default MainPage;