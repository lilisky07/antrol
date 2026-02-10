import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import FilterSection from './filtersection';   
import AntreanTable from './antrean';
import AmbilModal from './ambil';
import BatalModal from './batal';
import Card from './atoms/card';
import Button from './atoms/button';
import Input from './atoms/input';

// ← Hilangkan API_BASE_URL hardcode, pakai relative path saja
// Untuk production: semua request ke /api/... akan dihandle oleh server yang sama

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
      const res = await axios.post('/api/antrean/ambil', {  // ← relative path
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
      const res = await axios.get('/api/antrean/sisakuota', {  // ← relative path
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

  // ... (handler lain tetap sama)

  // ── Fetch Poli List ──
  useEffect(() => {
    const fetchPoliList = async () => {
      try {
        const res = await axios.get('/api/antrean/poli-list');  // ← relative
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

  // ── Fetch Antrean List ──
  const fetchAntrean = useCallback(async () => {
    setLoading(true);
    setErrorMsg('');
    try {
      const res = await axios.get('/api/antrean/public-list', {  // ← relative
        params: {
          page: currentPage,
          per_page: perPage,
          search: debouncedSearch || undefined,
          tgl_rencana: selectedDate || undefined,
          poli: selectedPoli || undefined,
          dokter: selectedDokter || undefined,
        },
      });

      console.log("API Response:", res.data);

      if (res.data.success) {
        const rawData = res.data.data?.data || [];
        const mappedData = rawData.map(item => ({
          ...item,
          status: item.status || (item.kode_booking ? 'Checkin' : 'Belum'),
          isBooked: !!item.is_booked || !!item.kode_booking || (item.status === 'Checkin'),
        }));
        setAntrean(mappedData);
        setTotalPages(10);
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

  // return JSX tetap sama
  // ...
}

export default MainPage;