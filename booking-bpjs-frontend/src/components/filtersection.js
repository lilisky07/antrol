// FilterSection.js
import React from 'react';
import Button from './atoms/button';
import Input from './atoms/input';
import Card from './atoms/card';

function FilterSection({
  searchTerm, setSearchTerm,
  selectedDate, setSelectedDate,
  selectedPoli, setSelectedPoli,
  selectedDokter, setSelectedDokter,
  poliList,
  resetFilters,
  setCurrentPage
}) {
  return (
    <Card fullWidth style={{ margin: '24px 0', padding: '24px' }}>
      <h2 style={{ margin: '0 0 20px', color: '#1d4ed8', fontSize: '24px' }}>Filter</h2>
      <div style={{
        display: 'grid',
        gridTemplateColumns: 'repeat(auto-fit, minmax(240px, 1fr))',
        gap: '20px'
      }}>
        <div>
          <label style={{ display: 'block', marginBottom: '8px', fontWeight: 500 }}>Cari Nama / No RM</label>
          <Input
            value={searchTerm}
            onChange={e => setSearchTerm(e.target.value)}
            placeholder="Ketik untuk mencari..."
          />
        </div>

        <div>
          <label style={{ display: 'block', marginBottom: '8px', fontWeight: 500 }}>Tanggal Rencana</label>
          <Input
            type="date"
            value={selectedDate}
            onChange={e => setSelectedDate(e.target.value)}
          />
        </div>

        <div>
          <label style={{ display: 'block', marginBottom: '8px', fontWeight: 500 }}>Poli</label>
          <select
            value={selectedPoli}
            onChange={e => setSelectedPoli(e.target.value)}
            style={{
              width: '100%',
              padding: '12px',
              borderRadius: '8px',
              border: '1px solid #d1d5db',
              fontSize: '16px',
              background: 'white'
            }}
          >
            <option value="">Semua Poli</option>
            {poliList.map(p => (
              <option key={p} value={p}>{p}</option>
            ))}
          </select>
        </div>

        <div style={{ display: 'flex', alignItems: 'flex-end', gap: '12px' }}>
          <Button variant="primary" onClick={() => setCurrentPage(1)}>
            Terapkan Filter
          </Button>
          <Button variant="outline" onClick={resetFilters}>
            Reset
          </Button>
        </div>
      </div>
    </Card>
  );
}

export default FilterSection;