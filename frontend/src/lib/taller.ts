// Número del taller para los botones de contacto del cliente. Se configura en el .env del
// frontend (VITE_TALLER_WHATSAPP, solo dígitos con código de país); sin él, no se muestran.
const NUMERO = ((import.meta.env.VITE_TALLER_WHATSAPP as string | undefined) ?? '').replace(/\D/g, '')

export const hayContactoTaller = NUMERO.length > 0

export function whatsappTaller(texto?: string): string {
  return `https://wa.me/${NUMERO}${texto ? `?text=${encodeURIComponent(texto)}` : ''}`
}

export function llamarTaller(): string {
  return `tel:+${NUMERO}`
}
