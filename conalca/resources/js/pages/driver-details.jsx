import React from 'react';
import { createRoot } from 'react-dom/client';
import DriverCallDetails from '../components/Calls/DriverCallDetails.jsx';

const container = document.getElementById('driver-details-root');
if (container) {
  const driverId = parseInt(container.dataset.driverId);
  const root = createRoot(container);
  root.render(<DriverCallDetails driverId={driverId} />);
}
