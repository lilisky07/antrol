import React, { useState, useEffect, useRef } from 'react';
import { Link } from 'react-router-dom';
import styled from 'styled-components';
import Button from '../atoms/button';

const Nav = styled.nav`
  background-color: ${props => props.scrolled ? '#1e3a8a' : '#1e40af'};
  color: white;
  padding: ${props => props.scrolled ? '12px 0' : '20px 0'};
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  box-shadow: ${props => props.scrolled ? '0 10px 30px rgba(0,0,0,0.2)' : '0 4px 20px rgba(0,0,0,0.1)'};
  transition: all 0.4s ease;
`;

const NavContainer = styled.div`
  max-width: 1600px;
  margin: 0 auto;
  padding: 0 40px;
  display: flex;
  justify-content: space-between;
  align-items: center;
`;

const Logo = styled.div`
  font-size: 10px;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 12px;
  animation: fadeInDown 0.8s ease-out;
  transition: transform 0.3s ease;
  cursor: pointer;
  &:hover { transform: scale(1.05); }
`;

// Pill Menu dengan glider HOVER + CLICK
const PillMenuWrapper = styled.div`
  position: relative;
  background-color: rgba(255, 255, 255, 0.21);
  padding: 5px;
  border-radius: 99px;
  display: flex;
  align-items: center;

  .tabs {
    display: flex;
    gap: 30px;
    position: relative;
    z-index: 2;
  }

  .tab {
    font-size: 13px;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.85);
    padding: 12px 15px;
    border-radius: 99px;
    cursor: pointer;
    transition: all 0.3s ease;
    white-space: nowrap;
    position: relative;
  }

  .tab:hover,
  .tab.active {
    color: #ffffffff;
  }

  .glider {
    position: absolute;
    top: 1px;
    height: 36px;
    background-color: rgba(237, 237, 237, 0.4); /* Lebih transparan biar hover keliatan jelas */
    border-radius: 99px;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1;
    pointer-events: none;
    box-shadow: 0 4px 12px rgba(134, 239, 172, 0.3);
  }

  /* Hover effect tambahan: scale sedikit */
  .tab:hover {
    transform: scale(1.02);
  }
`;

const ContactItem = styled.div`
  display: flex;
  align-items: center;
  gap: 8px;
  transition: color 0.3s ease;
  cursor: pointer;
  &:hover { color: #ffffffff; }
`;

const StyledButton = styled(Button)`
  animation: pulse 2s infinite;
  box-shadow: 0 0 20px rgba(255, 255, 255, 0.4);
  transition: all 0.3s ease;
  &:hover { transform: scale(1.08); }
`;

const Navbar = () => {
  const [scrolled, setScrolled] = useState(false);
  const [activeTab, setActiveTab] = useState(0);
  const [hoverTab, setHoverTab] = useState(null); // ← State baru untuk HOVER
  const tabsRef = useRef([]);
  const gliderRef = useRef(null);

  useEffect(() => {
    const handleScroll = () => setScrolled(window.scrollY > 20);
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  // Update glider berdasarkan active ATAU hover
  useEffect(() => {
    const targetTab = hoverTab !== null ? hoverTab : activeTab;
    const updateGlider = () => {
      const tab = tabsRef.current[targetTab];
      if (tab && gliderRef.current) {
        gliderRef.current.style.left = `${tab.offsetLeft}px`;
        gliderRef.current.style.width = `${tab.offsetWidth}px`;
      }
    };
    updateGlider();
    window.addEventListener('resize', updateGlider);
    return () => window.removeEventListener('resize', updateGlider);
  }, [activeTab, hoverTab]); // ← Dependensi tambah hoverTab

  const menuItems = [
    'Pasien & Pengunjung',
    'Layanan Kesehatan',
    'Pusat Layanan Unggulan',
    'Spesialis',
    'Informasi'
  ];

  const handleTabClick = (index) => {
    setActiveTab(index);
    setHoverTab(null); // Reset hover saat klik (biar balik ke active)
  };

  const handleTabHover = (index) => {
    setHoverTab(index);
  };

  const handleTabLeave = () => {
    setHoverTab(null); // Kembali ke activeTab saat mouse leave
  };

  return (
    <Nav scrolled={scrolled}>
      <NavContainer>
        <div style={{ display: 'flex', alignItems: 'center', gap: '60px' }}>
          <Logo>🏥 Gladish Medical Centre</Logo>

          <PillMenuWrapper>
            <div className="tabs">
              {menuItems.map((item, index) => (
                <div
                  key={index}
                  className={`tab ${activeTab === index || hoverTab === index ? 'active' : ''}`}
                  ref={el => tabsRef.current[index] = el}
                  onClick={() => handleTabClick(index)}
                  onMouseEnter={() => handleTabHover(index)}  // ← HOVER IN
                  onMouseLeave={handleTabLeave}              // ← HOVER OUT
                >
                  {item}
                </div>
              ))}
              <div className="glider" ref={gliderRef} />
            </div>
          </PillMenuWrapper>
        </div>

        <div style={{ display: 'flex', alignItems: 'center', gap: '30px' }}>
          <ContactItem><span style={{ fontSize: '16px' }}>📱</span> WhatsApp</ContactItem>
          <ContactItem><span style={{ fontSize: '16px' }}>☎️</span> 1-500-911</ContactItem>
          <Link to="/dashboard">
            <StyledButton variant="success" size="sm">Masuk Petugas</StyledButton>
          </Link>
        </div>
      </NavContainer>

      <style jsx>{`
        @keyframes fadeInDown {
          from { opacity: 0; transform: translateY(-30px); }
          to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulse {
          0% { box-shadow: 0 0 0 0 rgba(22,163,74,0.4); }
          70% { box-shadow: 0 0 0 15px rgba(22,163,74,0); }
          100% { box-shadow: 0 0 0 0 rgba(22,163,74,0); }
        }
      `}</style>
    </Nav>
  );
};

export default Navbar;