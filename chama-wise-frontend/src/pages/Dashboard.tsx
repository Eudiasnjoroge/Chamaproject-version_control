import { useState } from 'react';
import styles from './Dashboard.module.css'; // CSS Modules
import Card from '../components/Card';

export default function Dashboard() {
  const [campaigns, setCampaigns] = useState([
    { id: 1, title: 'School Fees', progress: 65 },
    { id: 2, title: 'Business Investment', progress: 30 }
  ]);

  return (
    <div className={styles.container}>
      <h1>Your Campaigns</h1>
      <div className={styles.grid}>
        {campaigns.map(campaign => (
          <Card key={campaign.id} campaign={campaign} />
        ))}
      </div>
    </div>
  );
}
