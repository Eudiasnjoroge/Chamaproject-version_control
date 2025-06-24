import React from 'react';
import { Container, Row, Col, ProgressBar, Card } from 'react-bootstrap';

const Dashboard = () => {
  const groupGoal = 100000;
  const currentTotal = 43000;
  const percentage = (currentTotal / groupGoal) * 100;

  return (
    <Container className="mt-4">
      <h2>Chama Group Dashboard</h2>

      <Card className="mt-3 p-3 shadow-sm">
        <h5>Goal Progress</h5>
        <ProgressBar now={percentage} label={`${percentage.toFixed(0)}%`} />
        <p className="mt-2">Ksh {currentTotal} of Ksh {groupGoal} saved</p>
      </Card>

      <Card className="mt-3 p-3 shadow-sm">
        <h5>Members</h5>
        <ul>
          <li>Mary - Ksh 5,000</li>
          <li>John - Ksh 10,000</li>
          <li>Faith - Ksh 7,000</li>
        </ul>
      </Card>

      <Card className="mt-3 p-3 shadow-sm">
        <h5>Next Contribution Due: 25 June 2025</h5>
        <button className="btn btn-success mt-2">Make a Contribution</button>
      </Card>
    </Container>
  );
};

export default Dashboard;
