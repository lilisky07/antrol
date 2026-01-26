import React from 'react';

const Card = ({ children, fullWidth = false }) => {
  return (
    <div style={{
      background: 'white',
      width: fullWidth ? '100%' : '100%',
      maxWidth: fullWidth ? 'none' : '480px',  // kalau fullWidth, nggak ada batas max
      margin: fullWidth ? '0' : '0 auto',     // kalau nggak full, center
      padding: fullWidth ? '30px' : '40px 35px',
      borderRadius: '20px',
      boxShadow: '0 20px 40px rgba(0,100,200,0.15)',
      border: '1px solid #dbeafe'
    }}>
      {children}
    </div>
  );
};

export default Card;