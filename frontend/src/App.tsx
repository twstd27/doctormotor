import { Navigate, Route, Routes } from 'react-router-dom'
import EvidenciasPage from './pages/EvidenciasPage'
import GoogleCallbackPage from './pages/GoogleCallbackPage'
import HistorialPublicoPage from './pages/HistorialPublicoPage'
import HomePage from './pages/HomePage'
import InspeccionPage from './pages/InspeccionPage'
import InvitacionTecnicoPage from './pages/InvitacionTecnicoPage'
import KanbanPage from './pages/KanbanPage'
import LoginPage from './pages/LoginPage'
import OrdenPage from './pages/OrdenPage'
import PerfilPage from './pages/PerfilPage'
import SeguimientoPage from './pages/SeguimientoPage'
import PresupuestoPage from './pages/PresupuestoPage'
import PrivacidadPage from './pages/PrivacidadPage'
import VehiculosPage from './pages/VehiculosPage'
import WhatsappVerifyPage from './pages/WhatsappVerifyPage'

function App() {
  return (
    <Routes>
      <Route path="/" element={<HomePage />} />
      <Route path="/login" element={<LoginPage />} />
      <Route path="/ordenes-trabajo" element={<KanbanPage />} />
      <Route path="/ordenes-trabajo/:id/inspeccion" element={<InspeccionPage />} />
      <Route path="/ordenes-trabajo/:id/evidencias" element={<EvidenciasPage />} />
      <Route path="/garaje" element={<Navigate to="/vehiculos" replace />} />
      <Route path="/ordenes-trabajo/:id" element={<OrdenPage />} />
      <Route path="/seguimiento/:id" element={<SeguimientoPage />} />
      <Route path="/perfil" element={<PerfilPage />} />
      <Route path="/vehiculos" element={<VehiculosPage />} />
      <Route path="/presupuestos/:id" element={<PresupuestoPage />} />
      <Route path="/auth/google/callback" element={<GoogleCallbackPage />} />
      <Route path="/auth/whatsapp/:token" element={<WhatsappVerifyPage />} />
      <Route path="/invitacion/:token" element={<InvitacionTecnicoPage />} />
      <Route path="/privacidad" element={<PrivacidadPage />} />
      <Route path="/qr/:token" element={<HistorialPublicoPage />} />
    </Routes>
  )
}

export default App
