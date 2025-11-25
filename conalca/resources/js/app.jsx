import React from 'react';
import ReactDOM from 'react-dom/client';
import './utils/errorHandler.js'; // Sistema global de manejo de errores
import './utils/cspDetector.js'; // Detector de problemas CSP
import UserPanel from './components/UserControlPanel/UserPanel';
import ChannelColumnContent from './components/Quotes/ChannelColumnContent';
import ChannelsWithCustomColumns from './components/Quotes/ChannelsWithCustomColumns';
// Modales se montan desde componentes padres (no directamente aquí)
import ChatBox from './components/Solicitation/ChatBox';
import SolicitationDetail from './components/Solicitation/SolicitationDetail';
import SolicitationForm from './components/Solicitation/SolicitationForm';
import SolicitationList from './components/Solicitation/SolicitationList';
import PricingActions from './components/Solicitation/PricingActions';
import SuperAdminActions from './components/Solicitation/SuperAdminActions';
import GoalDashboard       from './components/Goals/GoalDashboard';
import NotificationList    from './components/Goals/NotificationList';
import MyGoalProgress from './components/Goals/MyGoalProgress';
import SacCotizationView from './components/Cotizations/SacCotizationView';
import Wizard    from './components/SolicitudWizard/Wizard';

const components = [
    { id: 'user-panel', component: <UserPanel /> },
    { id: 'channel-column-content', component: <ChannelColumnContent /> },
    { id: 'channel-with-custom-columns', component: <ChannelsWithCustomColumns /> },
    { id: 'chat-box', component: <ChatBox /> },
    { id: 'solicitation-detail', component: <SolicitationDetail /> },
    { id: 'solicitation-form', component: <SolicitationForm /> },
    { id: 'solicitation-list', component: <SolicitationList /> },
    { id: 'pricing-actions', component: <PricingActions /> },
    { id: 'super-admin-actions', component: <SuperAdminActions /> },
    { id: 'goal-dashboard', component: <GoalDashboard /> },
    { id: 'notification-list', component: <NotificationList /> },
    { id: 'my-goal-progress', component: <MyGoalProgress /> },
    { id:'sac-cotizations', component:<SacCotizationView/> },
    // Wizard component removed from global rendering as it's used conditionally in ChannelColumnContent
];

components.forEach(({ id, component }) => {
    const element = document.getElementById(id);
    if (element) {
        ReactDOM.createRoot(element).render(component);
    }
});
