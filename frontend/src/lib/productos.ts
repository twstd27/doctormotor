import { api } from './api'
import { useAuthStore } from '../store/auth'

const API_URL = import.meta.env.VITE_API_URL as string

export interface Producto {
  id: number
  sku: string
  nombre: string
  categoria: string | null
  unidad_medida: string
  imagen_url: string | null
  stock_actual: number
  precio_venta: number
}

interface PaginatedResponse<T> {
  data: T[]
}

export async function buscarProductos(query: string): Promise<Producto[]> {
  if (!query.trim()) return []
  const res = await api<PaginatedResponse<Producto>>(`/productos?buscar=${encodeURIComponent(query)}`)
  return res.data
}

export interface NuevoProducto {
  sku: string
  nombre: string
  categoria: string
  unidad_medida: string
  precio_venta: string
  foto: File | null
}

export async function crearProducto(datos: NuevoProducto): Promise<Producto> {
  const token = useAuthStore.getState().token
  const form = new FormData()
  form.append('sku', datos.sku)
  form.append('nombre', datos.nombre)
  if (datos.categoria) form.append('categoria', datos.categoria)
  form.append('unidad_medida', datos.unidad_medida)
  if (datos.precio_venta) form.append('precio_venta', datos.precio_venta)
  if (datos.foto) form.append('imagen', datos.foto)

  const res = await fetch(`${API_URL}/productos`, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
    },
    body: form,
  })

  const body = await res.json().catch(() => null)

  if (!res.ok) {
    throw new Error(body?.message ?? 'No se pudo crear el producto')
  }

  return body.data as Producto
}
