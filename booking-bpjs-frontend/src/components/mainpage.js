import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import FilterSection from './filtersection';   
import AntreanTable from './antrean';
import AmbilModal from './ambil';
import BatalModal from './batal';
import Card from './atoms/card';
import Button from './atoms/button';
import Input from './atoms/input';

// // ← Tambahkan ini di atas (bisa diganti ke .env nanti)
// const API_BASE_URL = 'http://127.0.0.1:8000';  // atau http://localhost:8000 kalau prefer

function MainPage() {
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
  const [sisaKuota, setSisaKuota] = useState(null);
  const [cekKuotaLoading, setCekKuotaLoading] = useState(false);

  const [showBatalModal, setShowBatalModal] = useState(false);
  const [selectedBatal, setSelectedBatal] = useState(null);
  const [batalLoading, setBatalLoading] = useState(false);

  // ── Handler ──
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
     const res = await axios.post('/api/antrean/ambil', {
        no_rm: selectedItem.no_rm,
        no_surat: selectedItem.no_surat,
        kd_poli: selectedItem.kd_poli,
        kd_dokter: selectedItem.kd_dokter,
        tgl_antrean: selectedTanggal,
      });

      if (res.data.success) {
        setAmbilSuccess(true);
        setAntrean(prev =>
          prev.map(item =>
            item.no_surat === selectedItem.no_surat
              ? { ...item, isBooked: true, status: 'Checkin' }
              : item
          )
        );
      } else {
        alert(res.data.message || 'Gagal mengambil antrean');
      }
    } catch (err) {
      alert(err.response?.data?.message || 'Terjadi kesalahan server');
    } finally {
      setAmbilLoading(false);
    }
  };

  const cekSisaKuota = async (item, tanggal) => {
    if (!item || !tanggal) return;
    setCekKuotaLoading(true);
    try {
      const res = await axios.get(`/api/antrean/sisakuota`, {
        params: {
          kd_poli: item.kd_poli,
          kd_dokter: item.kd_dokter,
          tanggal: tanggal,
        },
      });

      if (res.data.success) {
        setSisaKuota(res.data.sisa_kuota ?? 0);
      } else {
        setSisaKuota(0);
      }
    } catch (err) {
      console.error('Error cek sisa kuota:', err);
      setSisaKuota(0);
    } finally {
      setCekKuotaLoading(false);
    }
  };

  useEffect(() => {
    if (showAmbilModal && selectedItem && selectedTanggal) {
      cekSisaKuota(selectedItem, selectedTanggal);
    }
  }, [showAmbilModal, selectedItem, selectedTanggal]);

  const handleBatal = (item) => {
    setSelectedBatal(item);
    setShowBatalModal(true);
  };

  const confirmBatal = async () => {
    setBatalLoading(true);
    try {
      setAntrean(prev =>
        prev.map(item =>
          item.no_surat === selectedBatal.no_surat
            ? { ...item, isBooked: false, status: 'Batal' }
            : item
        )
      );
      setShowBatalModal(false);
    } catch (err) {
      alert('Gagal membatalkan: ' + (err.message || 'Unknown error'));
    } finally {
      setBatalLoading(false);
    }
  };

  const resetFilters = () => {
    setSearchTerm('');
    setSelectedDate('');
    setSelectedPoli('');
    setSelectedDokter('');
    setCurrentPage(1);
  };

  // ── Fetch Poli List ──
  useEffect(() => {
    const fetchPoliList = async () => {
      try {
        const res = await axios.get(`/api/antrean/poli-list`);
        if (res.data.success) {
          const poliData = res.data.data || [];
          if (Array.isArray(poliData) && typeof poliData[0] === 'string') {
            setPoliList([...new Set(poliData)].sort());
          } else {
            const poliNames = poliData
              .map(item => item.nm_poli || item.nm_poli_bpjs || item.nama_poli || '')
              .filter(Boolean);
            setPoliList([...new Set(poliNames)].sort());
          }
        }
      } catch (err) {
        console.error('Error fetch poli-list:', err);
      }
    };

    fetchPoliList();
  }, []);

  // ── Debounce Search ──
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearch(searchTerm);
      setCurrentPage(1);
    }, 600);
    return () => clearTimeout(timer);
  }, [searchTerm]);

  // ── Fetch Antrean List ──
  const fetchAntrean = useCallback(async () => {
    setLoading(true);
    setErrorMsg('');
    try {
      const res = await axios.get(
        `/api/antrean/public-list`,
        {
          params: {
            page: currentPage,
            per_page: perPage,
            search: debouncedSearch || undefined,
            tgl_rencana: selectedDate || undefined,
            poli: selectedPoli || undefined,
            dokter: selectedDokter || undefined,
          },
        }
      );

      console.log("API Response:", res.data);

      if (res.data.success) {
        const rawData = res.data.data?.data || [];
        const mappedData = rawData.map(item => ({
          ...item,
          status: item.status || (item.kode_booking ? 'Checkin' : 'Belum'),
          isBooked: !!item.is_booked || !!item.kode_booking || (item.status === 'Checkin'),
        }));
        setAntrean(mappedData);
        setTotalPages(10); // nanti bisa diganti dari res.data jika ada pagination real
      } else {
        setErrorMsg(res.data.message || 'Gagal memuat data');
      }
    } catch (err) {
      console.error('Fetch error:', err);
      setErrorMsg('Gagal terhubung ke server. Cek koneksi atau backend.');
    } finally {
      setLoading(false);
    }
  }, [currentPage, debouncedSearch, selectedDate, selectedPoli, selectedDokter]);

  useEffect(() => {
    fetchAntrean();
  }, [fetchAntrean]);

  return (
    <div style={{ minHeight: '100vh', background: 'linear-gradient(135deg, #f0f7ff, #e0f2fe)', padding: '16px 20px' }}>
      <div style={{ maxWidth: '1600px', margin: '0 auto' }}>
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

        <AmbilModal
          show={showAmbilModal}
          setShow={setShowAmbilModal}
          selectedItem={selectedItem}
          selectedTanggal={selectedTanggal}
          setSelectedTanggal={setSelectedTanggal}
          ambilLoading={ambilLoading}
          ambilSuccess={ambilSuccess}
          confirmAmbil={confirmAmbil}
          sisaKuota={sisaKuota}
          cekKuotaLoading={cekKuotaLoading}
        />

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