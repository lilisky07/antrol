import React from 'react';
import styled from 'styled-components';

const LayananCard = ({ icon, title }) => {
  return (
    <StyledWrapper>
      <a className="card1" href="#">
        <div className="icon">{icon}</div>
        <p>{title}</p>
        <div className="go-corner">
          <div className="go-arrow">→</div>
        </div>
      </a>
    </StyledWrapper>
  );
};

const StyledWrapper = styled.div`
  .card1 {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
    min-width: 240px;
    max-width: 280px;
    height: 180px;
    background-color: #f8fafc;
    border-radius: 20px;
    padding: 10px;
    margin: 10px;
    text-decoration: none;
    z-index: 0;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
  }

  .icon {
    font-size: 60px;
    margin-bottom: 16px;
  }

  .card1 p {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
    text-align: center;
    transition: color 0.3s ease-out;
  }

  .go-corner {
    display: flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    width: 40px;
    height: 40px;
    overflow: hidden;
    top: 0;
    right: 0;
    background-color: #1e40af; /* biru Gladish */
    border-radius: 0 20px 0 40px;
  }

  .go-arrow {
    margin-top: -4px;
    margin-right: -4px;
    color: white;
    font-size: 28px;
    font-family: courier, sans;
  }

  .card1:before {
    content: "";
    position: absolute;
    z-index: -1;
    top: -20px;
    right: -20px;
    background: #1e40af; /* biru Gladish */
    height: 40px;
    width: 40px;
    border-radius: 40px;
    transform: scale(1);
    transform-origin: 50% 50%;
    transition: transform 0.35s ease-out;
  }

  .card1:hover:before {
    transform: scale(28);
  }

  .card1:hover p {
    color: rgba(255, 255, 255, 0.95);
  }

  .card1:hover .icon {
    color: rgba(255, 255, 255, 0.9);
    transition: color 0.3s ease-out;
  }

  .card1:hover {
    transform: translateY(-12px);
    box-shadow: 0 25px 50px rgba(30,64,175,0.25);
  }
`;

export default LayananCard;