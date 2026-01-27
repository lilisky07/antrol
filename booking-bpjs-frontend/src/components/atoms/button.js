import React from 'react';
import styled from 'styled-components';

const Button = ({ children, variant = 'primary', disabled = false, onClick, size = 'md' }) => {
  return (
    <StyledButton 
      variant={variant} 
      disabled={disabled} 
      onClick={onClick}
      size={size}
    >
      {children}
    </StyledButton>
  );
};

const StyledButton = styled.button`
  padding: ${({ size }) => 
    size === 'sm' ? '8px 16px' : 
    size === 'lg' ? '16px 32px' : '12px 24px'};
  font-size: ${({ size }) => 
    size === 'sm' ? '14px' : 
    size === 'lg' ? '18px' : '16px'};
  font-weight: 600;
  border: none;
  border-radius: 30px;
  cursor: ${({ disabled }) => disabled ? 'not-allowed' : 'pointer'};
  transition: all 0.3s ease;
  opacity: ${({ disabled }) => disabled ? 0.6 : 1};

  /* Variant warna */
  background-color: ${({ variant, disabled }) => {
    if (disabled) return '#94a3b8';
    switch (variant) {
      case 'success': return '#86efac'; // hijau soft
      case 'danger': return '#fca5a5';  // merah soft
      case 'primary': return '#93c5fd'; // biru soft
      default: return '#93c5fd';
    }
  }};

  color: ${({ variant }) => 
    variant === 'primary' || variant === 'success' || variant === 'danger' ? '#1e293b' : '#fff'};

  &:hover:not(:disabled) {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    
    ${({ variant }) => {
      switch (variant) {
        case 'success': return `
          background-color: #4ade80;
          box-shadow: 0 0 0 5px #4ade805f;
        `;
        case 'danger': return `
          background-color: #f87171;
          box-shadow: 0 0 0 5px #f871715f;
        `;
        case 'primary': return `
          background-color: #3b82f6;
          box-shadow: 0 0 0 5px #3b82f65f;
        `;
        default: return `
          background-color: #3b82f6;
          box-shadow: 0 0 0 5px #3b82f65f;
        `;
      }
    }}
  }
`;

export default Button;