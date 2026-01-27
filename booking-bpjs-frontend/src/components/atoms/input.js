import React from 'react';
import styled from 'styled-components';

const Input = ({ type = 'text', placeholder, value, onChange, ...props }) => {
  return (
    <StyledInput
      type={type}
      placeholder={placeholder}
      value={value}
      onChange={onChange}
      {...props}
    />
  );
};

const StyledInput = styled.input`
  border: 2px solid transparent;
  width: 80%;
  height: 2.8em;
  padding: 0 1em;
  font-size: 16px;
  outline: none;
  background-color: #f8fafc;
  border-radius: 40px;
  transition: all 0.4s ease;
  color: #1e293b;

  &::placeholder {
    color: #94a3b8;
  }

  &:hover {
    background-color: #ffffff;
    border-color: #93c5fd;
    box-shadow: 0 0 0 6px rgba(147, 197, 253, 0.2);
  }

  &:focus {
    background-color: #ffffff;
    border-color: #3b82f6;
    box-shadow: 0 0 0 6px rgba(59, 130, 246, 0.3);
  }
`;

export default Input;