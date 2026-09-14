import { useEffect, useRef, useState } from 'react'
import { CaretLeft, Camera, ImagesSquare, MagnifyingGlass, Package, X } from '@phosphor-icons/react'
import { crearCostoDirecto, type TipoCosto } from '../lib/costosDirectos'
import { buscarProductos, crearProducto, type Producto } from '../lib/productos'
import { useAuthStore } from '../store/auth'
import { useToastStore } from '../store/toast'
import BottomSheet from './BottomSheet'

const TIPOS: { value: TipoCosto; label: string }[] = [
  { value: 'repuesto', label: 'Repuesto' },
  { value: 'mano_obra', label: 'Mano de obra' },
  { value: 'tercerizado', label: 'Trabajo tercerizado' },
]

interface RegistrarCostoSheetProps {
  otId: number
  onClose: () => void
  onRegistrado: () => void
}

export default function RegistrarCostoSheet({ otId, onClose, onRegistrado }: RegistrarCostoSheetProps) {
  const user = useAuthStore((s) => s.user)
  const showToast = useToastStore((s) => s.show)

  const [paso, setPaso] = useState<'costo' | 'nuevo-producto'>('costo')
  const [tipo, setTipo] = useState<TipoCosto>('repuesto')
  const [busqueda, setBusqueda] = useState('')
  const [resultados, setResultados] = useState<Producto[]>([])
  const [buscando, setBuscando] = useState(false)
  const [productoSeleccionado, setProductoSeleccionado] = useState<Producto | null>(null)
  const [cantidad, setCantidad] = useState('1')
  const [costoUnitario, setCostoUnitario] = useState('')
  const [descripcion, setDescripcion] = useState('')
  const [enviando, setEnviando] = useState(false)
  const [error, setError] = useState<string | null>(null)

  // Nuevo producto
  const [nSku, setNSku] = useState('')
  const [nNombre, setNNombre] = useState('')
  const [nCategoria, setNCategoria] = useState('')
  const [nUnidad, setNUnidad] = useState('unidad')
  const [nPrecioVenta, setNPrecioVenta] = useState('')
  const [nFoto, setNFoto] = useState<File | null>(null)
  const [creandoProducto, setCreandoProducto] = useState(false)
  const fotoRef = useRef<HTMLInputElement>(null)
  const galeriaRef = useRef<HTMLInputElement>(null)

  useEffect(() => {
    if (tipo !== 'repuesto' || !busqueda.trim()) {
      setResultados([])
      return
    }
    setBuscando(true)
    const t = setTimeout(() => {
      buscarProductos(busqueda)
        .then(setResultados)
        .finally(() => setBuscando(false))
    }, 350)
    return () => clearTimeout(t)
  }, [busqueda, tipo])

  function seleccionarProducto(p: Producto) {
    setProductoSeleccionado(p)
    setBusqueda('')
    setResultados([])
    if (p.precio_venta) setCostoUnitario(String(p.precio_venta))
  }

  async function handleCrearProducto() {
    if (!nSku.trim() || !nNombre.trim() || !nUnidad.trim()) {
      setError('SKU, nombre y unidad de medida son obligatorios.')
      return
    }
    setCreandoProducto(true)
    setError(null)
    try {
      const producto = await crearProducto({
        sku: nSku.trim(),
        nombre: nNombre.trim(),
        categoria: nCategoria.trim(),
        unidad_medida: nUnidad.trim(),
        precio_venta: nPrecioVenta,
        foto: nFoto,
      })
      seleccionarProducto(producto)
      showToast('Producto creado')
      setPaso('costo')
    } catch (e) {
      setError(e instanceof Error ? e.message : 'No se pudo crear el producto')
    } finally {
      setCreandoProducto(false)
    }
  }

  async function handleSubmit() {
    if (tipo === 'repuesto' && !productoSeleccionado) {
      setError('Selecciona un producto o crea uno nuevo.')
      return
    }
    if (!descripcion.trim()) {
      setError('Agrega una descripción.')
      return
    }
    if (!costoUnitario || Number(costoUnitario) < 0) {
      setError('Ingresa el costo unitario.')
      return
    }

    setEnviando(true)
    setError(null)
    try {
      await crearCostoDirecto(otId, {
        tipo,
        producto_id: tipo === 'repuesto' ? productoSeleccionado?.id : null,
        tecnico_id: tipo !== 'repuesto' && user?.rol === 'operador_tecnico' ? user.id : null,
        descripcion: descripcion.trim(),
        cantidad: Number(cantidad) || 1,
        costo_unitario: Number(costoUnitario),
      })
      showToast('Costo registrado')
      onRegistrado()
    } catch (e) {
      setError(e instanceof Error ? e.message : 'No se pudo registrar el costo')
    } finally {
      setEnviando(false)
    }
  }

  if (paso === 'nuevo-producto') {
    return (
      <BottomSheet onClose={onClose}>
        <button
          type="button"
          onClick={() => setPaso('costo')}
          className="mb-2 flex items-center gap-1 text-sm font-medium text-app-muted"
        >
          <CaretLeft size={16} /> Volver
        </button>
        <h2 className="text-lg font-semibold tracking-[-0.02em]">Crear producto nuevo</h2>

        <div className="mt-3 flex flex-col gap-2.5">
          <input
            value={nSku}
            onChange={(e) => setNSku(e.target.value)}
            placeholder="Código / SKU"
            className="h-12 w-full rounded-xl bg-app-surface-2 px-3.5 text-[14px]"
            style={{ border: '1px solid var(--color-app-line)' }}
          />
          <input
            value={nNombre}
            onChange={(e) => setNNombre(e.target.value)}
            placeholder="Nombre del producto"
            className="h-12 w-full rounded-xl bg-app-surface-2 px-3.5 text-[14px]"
            style={{ border: '1px solid var(--color-app-line)' }}
          />
          <div className="grid grid-cols-2 gap-2.5">
            <input
              value={nCategoria}
              onChange={(e) => setNCategoria(e.target.value)}
              placeholder="Categoría"
              className="h-12 w-full rounded-xl bg-app-surface-2 px-3.5 text-[14px]"
              style={{ border: '1px solid var(--color-app-line)' }}
            />
            <input
              value={nUnidad}
              onChange={(e) => setNUnidad(e.target.value)}
              placeholder="Unidad (ej. unidad, litro)"
              className="h-12 w-full rounded-xl bg-app-surface-2 px-3.5 text-[14px]"
              style={{ border: '1px solid var(--color-app-line)' }}
            />
          </div>
          <input
            value={nPrecioVenta}
            onChange={(e) => setNPrecioVenta(e.target.value)}
            type="number"
            inputMode="decimal"
            placeholder="Precio de venta (Bs)"
            className="h-12 w-full rounded-xl bg-app-surface-2 px-3.5 text-[14px]"
            style={{ border: '1px solid var(--color-app-line)' }}
          />

          <input ref={fotoRef} type="file" accept="image/*" capture="environment" className="hidden" onChange={(e) => setNFoto(e.target.files?.[0] ?? null)} />
          <input ref={galeriaRef} type="file" accept="image/*" className="hidden" onChange={(e) => setNFoto(e.target.files?.[0] ?? null)} />

          {nFoto ? (
            <div className="flex items-center gap-3 rounded-xl bg-app-surface-2 p-2.5" style={{ border: '1px solid var(--color-app-line)' }}>
              <img src={URL.createObjectURL(nFoto)} alt="Foto del producto" className="size-12 rounded-lg object-cover" />
              <span className="flex-1 truncate text-xs text-app-muted">{nFoto.name}</span>
              <button type="button" onClick={() => setNFoto(null)} className="flex size-7 items-center justify-center rounded-full bg-app-surface-3">
                <X size={14} />
              </button>
            </div>
          ) : (
            <div className="grid grid-cols-2 gap-2.5">
              <button
                type="button"
                onClick={() => fotoRef.current?.click()}
                className="flex h-11 items-center justify-center gap-2 rounded-xl text-sm font-medium"
                style={{ border: '1px solid var(--color-app-line)' }}
              >
                <Camera size={17} /> Tomar foto
              </button>
              <button
                type="button"
                onClick={() => galeriaRef.current?.click()}
                className="flex h-11 items-center justify-center gap-2 rounded-xl text-sm font-medium"
                style={{ border: '1px solid var(--color-app-line)' }}
              >
                <ImagesSquare size={17} /> Elegir foto
              </button>
            </div>
          )}
        </div>

        {error && <p className="mt-3 text-xs text-cor">{error}</p>}

        <button
          type="button"
          disabled={creandoProducto}
          onClick={handleCrearProducto}
          className="mt-5 h-[54px] w-full rounded-xl bg-lime-500 text-[15px] font-semibold text-lime-ink disabled:opacity-60"
          style={{ boxShadow: 'var(--shadow-cta-lime)' }}
        >
          {creandoProducto ? 'Creando…' : 'Crear y usar este producto'}
        </button>
      </BottomSheet>
    )
  }

  return (
    <BottomSheet onClose={onClose}>
      <h2 className="text-lg font-semibold tracking-[-0.02em]">Registrar costo</h2>

      <div className="mt-3 flex gap-2">
        {TIPOS.map((t) => (
          <button
            key={t.value}
            type="button"
            onClick={() => {
              setTipo(t.value)
              setProductoSeleccionado(null)
              setCostoUnitario('')
            }}
            className={
              tipo === t.value
                ? 'flex-1 rounded-xl bg-lime-500/15 px-2 py-2.5 text-[12.5px] font-semibold text-lime-txt'
                : 'flex-1 rounded-xl bg-app-surface-2 px-2 py-2.5 text-[12.5px] font-medium text-app-muted'
            }
            style={{ border: `1px solid ${tipo === t.value ? 'var(--color-lime-500)' : 'var(--color-app-line)'}` }}
          >
            {t.label}
          </button>
        ))}
      </div>

      {tipo === 'repuesto' && (
        <div className="mt-3">
          {productoSeleccionado ? (
            <div className="flex items-center gap-3 rounded-xl bg-app-surface-2 p-2.5" style={{ border: '1px solid var(--color-app-line)' }}>
              {productoSeleccionado.imagen_url ? (
                <img src={productoSeleccionado.imagen_url} alt="" className="size-10 rounded-lg object-cover" />
              ) : (
                <div className="flex size-10 items-center justify-center rounded-lg bg-app-surface-3">
                  <Package size={18} className="text-app-faint" />
                </div>
              )}
              <div className="min-w-0 flex-1">
                <p className="truncate text-[13.5px] font-medium">{productoSeleccionado.nombre}</p>
                <p className="text-[11px] text-app-faint">Stock: {productoSeleccionado.stock_actual}</p>
              </div>
              <button
                type="button"
                onClick={() => setProductoSeleccionado(null)}
                className="flex size-7 items-center justify-center rounded-full bg-app-surface-3"
              >
                <X size={14} />
              </button>
            </div>
          ) : (
            <>
              <div className="relative">
                <MagnifyingGlass size={16} className="absolute top-1/2 left-3.5 -translate-y-1/2 text-app-faint" />
                <input
                  value={busqueda}
                  onChange={(e) => setBusqueda(e.target.value)}
                  placeholder="Buscar producto por nombre…"
                  className="h-12 w-full rounded-xl bg-app-surface-2 pr-3.5 pl-10 text-[14px]"
                  style={{ border: '1px solid var(--color-app-line)' }}
                />
              </div>

              {buscando && <p className="mt-2 text-xs text-app-faint">Buscando…</p>}

              {resultados.length > 0 && (
                <div className="mt-2 flex flex-col gap-1.5">
                  {resultados.map((p) => (
                    <button
                      key={p.id}
                      type="button"
                      onClick={() => seleccionarProducto(p)}
                      className="flex items-center gap-3 rounded-xl bg-app-surface-2 p-2.5 text-left"
                      style={{ border: '1px solid var(--color-app-line)' }}
                    >
                      <div className="min-w-0 flex-1">
                        <p className="truncate text-[13.5px] font-medium">{p.nombre}</p>
                        <p className="text-[11px] text-app-faint">Stock: {p.stock_actual} · Bs {p.precio_venta}</p>
                      </div>
                    </button>
                  ))}
                </div>
              )}

              <button
                type="button"
                onClick={() => setPaso('nuevo-producto')}
                className="mt-2.5 flex h-11 w-full items-center justify-center gap-2 rounded-xl text-sm font-medium text-lime-txt"
                style={{ border: '1px dashed var(--color-lime-500)' }}
              >
                + No está en la lista, crear producto nuevo
              </button>
            </>
          )}
        </div>
      )}

      <div className="mt-3 flex flex-col gap-2.5">
        <textarea
          value={descripcion}
          onChange={(e) => setDescripcion(e.target.value)}
          placeholder="Descripción"
          rows={2}
          className="w-full resize-none rounded-xl bg-app-surface-2 p-3.5 text-[14px]"
          style={{ border: '1px solid var(--color-app-line)' }}
        />
        <div className="grid grid-cols-2 gap-2.5">
          <input
            value={cantidad}
            onChange={(e) => setCantidad(e.target.value)}
            type="number"
            inputMode="decimal"
            placeholder="Cantidad"
            className="h-12 w-full rounded-xl bg-app-surface-2 px-3.5 text-[14px]"
            style={{ border: '1px solid var(--color-app-line)' }}
          />
          <input
            value={costoUnitario}
            onChange={(e) => setCostoUnitario(e.target.value)}
            type="number"
            inputMode="decimal"
            placeholder="Costo unitario (Bs)"
            className="h-12 w-full rounded-xl bg-app-surface-2 px-3.5 text-[14px]"
            style={{ border: '1px solid var(--color-app-line)' }}
          />
        </div>
      </div>

      {error && <p className="mt-3 text-xs text-cor">{error}</p>}

      <button
        type="button"
        disabled={enviando}
        onClick={handleSubmit}
        className="mt-5 h-[54px] w-full rounded-xl bg-lime-500 text-[15px] font-semibold text-lime-ink disabled:opacity-60"
        style={{ boxShadow: 'var(--shadow-cta-lime)' }}
      >
        {enviando ? 'Registrando…' : 'Registrar costo'}
      </button>
    </BottomSheet>
  )
}
