import { useEffect, useRef, useState } from 'react'
import { CaretLeft, Camera, ImagesSquare, MagnifyingGlass, Package, Trash, X } from '@phosphor-icons/react'
import {
  agregarAdicional,
  crearPresupuesto,
  enviarPresupuesto,
  listarPresupuestosDeOt,
  type NuevoPresupuestoItem,
  type Presupuesto,
} from '../lib/presupuestos'
import { buscarProductos, crearProducto, type Producto } from '../lib/productos'
import { useToastStore } from '../store/toast'
import BottomSheet from './BottomSheet'

const TIPOS: { value: NuevoPresupuestoItem['tipo']; label: string }[] = [
  { value: 'repuesto', label: 'Repuesto' },
  { value: 'mano_obra', label: 'Mano de obra' },
  { value: 'tercerizado', label: 'Trabajo tercerizado' },
]

interface ItemPendiente extends NuevoPresupuestoItem {
  key: string
  productoNombre: string | null
}

interface PresupuestoSheetProps {
  otId: number
  onClose: () => void
  onEnviado: () => void
}

export default function PresupuestoSheet({ otId, onClose, onEnviado }: PresupuestoSheetProps) {
  const showToast = useToastStore((s) => s.show)

  const [cargando, setCargando] = useState(true)
  const [presupuestoExistente, setPresupuestoExistente] = useState<Presupuesto | null>(null)

  const [paso, setPaso] = useState<'resumen' | 'item' | 'nuevo-producto'>('resumen')
  const [items, setItems] = useState<ItemPendiente[]>([])
  const [enviando, setEnviando] = useState(false)
  const [error, setError] = useState<string | null>(null)

  // Formulario de ítem en construcción
  const [tipo, setTipo] = useState<NuevoPresupuestoItem['tipo']>('repuesto')
  const [busqueda, setBusqueda] = useState('')
  const [resultados, setResultados] = useState<Producto[]>([])
  const [buscando, setBuscando] = useState(false)
  const [productoSeleccionado, setProductoSeleccionado] = useState<Producto | null>(null)
  const [cantidad, setCantidad] = useState('1')
  const [precioUnitario, setPrecioUnitario] = useState('')
  const [descripcion, setDescripcion] = useState('')

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
    listarPresupuestosDeOt(otId)
      .then((lista) => setPresupuestoExistente(lista[0] ?? null))
      .finally(() => setCargando(false))
  }, [otId])

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
    if (p.precio_venta) setPrecioUnitario(String(p.precio_venta))
  }

  function resetFormularioItem() {
    setTipo('repuesto')
    setProductoSeleccionado(null)
    setBusqueda('')
    setCantidad('1')
    setPrecioUnitario('')
    setDescripcion('')
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
      setPaso('item')
    } catch (e) {
      setError(e instanceof Error ? e.message : 'No se pudo crear el producto')
    } finally {
      setCreandoProducto(false)
    }
  }

  function agregarItemALaLista() {
    if (tipo === 'repuesto' && !productoSeleccionado) {
      setError('Selecciona un producto o crea uno nuevo.')
      return
    }
    if (!descripcion.trim()) {
      setError('Agrega una descripción.')
      return
    }
    if (!precioUnitario || Number(precioUnitario) < 0) {
      setError('Ingresa el precio unitario.')
      return
    }

    setError(null)
    setItems((prev) => [
      ...prev,
      {
        key: crypto.randomUUID(),
        tipo,
        producto_id: tipo === 'repuesto' ? productoSeleccionado?.id : null,
        productoNombre: tipo === 'repuesto' ? (productoSeleccionado?.nombre ?? null) : null,
        descripcion: descripcion.trim(),
        cantidad: Number(cantidad) || 1,
        precio_unitario: Number(precioUnitario),
      },
    ])
    resetFormularioItem()
    setPaso('resumen')
  }

  const total = items.reduce((acc, i) => acc + i.cantidad * i.precio_unitario, 0)

  async function handleEnviarPresupuesto() {
    if (items.length === 0) {
      setError('Agrega al menos un ítem.')
      return
    }
    setEnviando(true)
    setError(null)
    try {
      const nuevo = await crearPresupuesto(
        otId,
        items.map(({ tipo, producto_id, descripcion, cantidad, precio_unitario }) => ({
          tipo,
          producto_id,
          descripcion,
          cantidad,
          precio_unitario,
        })),
      )
      await enviarPresupuesto(nuevo.id)
      showToast('Presupuesto enviado al cliente')
      onEnviado()
    } catch (e) {
      setError(e instanceof Error ? e.message : 'No se pudo enviar el presupuesto')
    } finally {
      setEnviando(false)
    }
  }

  async function handleAgregarAdicional() {
    if (tipo === 'repuesto' && !productoSeleccionado) {
      setError('Selecciona un producto o crea uno nuevo.')
      return
    }
    if (!descripcion.trim()) {
      setError('Agrega una descripción.')
      return
    }
    if (!precioUnitario || Number(precioUnitario) < 0) {
      setError('Ingresa el precio unitario.')
      return
    }

    setEnviando(true)
    setError(null)
    try {
      await agregarAdicional(otId, {
        tipo,
        producto_id: tipo === 'repuesto' ? productoSeleccionado?.id : null,
        descripcion: descripcion.trim(),
        cantidad: Number(cantidad) || 1,
        precio_unitario: Number(precioUnitario),
      })
      showToast('Adicional enviado — el cliente ya puede aprobarlo')
      onEnviado()
    } catch (e) {
      setError(e instanceof Error ? e.message : 'No se pudo registrar el adicional')
    } finally {
      setEnviando(false)
    }
  }

  const inputCls = 'h-12 w-full rounded-xl bg-app-surface-2 px-3.5 text-[14px]'
  const inputBorder = { border: '1px solid var(--color-app-line)' }

  if (cargando) {
    return (
      <BottomSheet onClose={onClose}>
        <p className="py-6 text-center text-sm text-app-faint">Cargando…</p>
      </BottomSheet>
    )
  }

  if (paso === 'nuevo-producto') {
    return (
      <BottomSheet onClose={onClose}>
        <button type="button" onClick={() => setPaso('item')} className="mb-2 flex items-center gap-1 text-sm font-medium text-app-muted">
          <CaretLeft size={16} /> Volver
        </button>
        <h2 className="text-lg font-semibold tracking-[-0.02em]">Crear producto nuevo</h2>

        <div className="mt-3 flex flex-col gap-2.5">
          <input value={nSku} onChange={(e) => setNSku(e.target.value)} placeholder="Código / SKU" className={inputCls} style={inputBorder} />
          <input value={nNombre} onChange={(e) => setNNombre(e.target.value)} placeholder="Nombre del producto" className={inputCls} style={inputBorder} />
          <div className="grid grid-cols-2 gap-2.5">
            <input value={nCategoria} onChange={(e) => setNCategoria(e.target.value)} placeholder="Categoría" className={inputCls} style={inputBorder} />
            <input value={nUnidad} onChange={(e) => setNUnidad(e.target.value)} placeholder="Unidad (ej. unidad, litro)" className={inputCls} style={inputBorder} />
          </div>
          <input
            value={nPrecioVenta}
            onChange={(e) => setNPrecioVenta(e.target.value)}
            type="number"
            inputMode="decimal"
            placeholder="Precio de venta (Bs)"
            className={inputCls}
            style={inputBorder}
          />

          <input ref={fotoRef} type="file" accept="image/*" capture="environment" className="hidden" onChange={(e) => setNFoto(e.target.files?.[0] ?? null)} />
          <input ref={galeriaRef} type="file" accept="image/*" className="hidden" onChange={(e) => setNFoto(e.target.files?.[0] ?? null)} />

          {nFoto ? (
            <div className="flex items-center gap-3 rounded-xl bg-app-surface-2 p-2.5" style={inputBorder}>
              <img src={URL.createObjectURL(nFoto)} alt="Foto del producto" className="size-12 rounded-lg object-cover" />
              <span className="flex-1 truncate text-xs text-app-muted">{nFoto.name}</span>
              <button type="button" onClick={() => setNFoto(null)} className="flex size-7 items-center justify-center rounded-full bg-app-surface-3">
                <X size={14} />
              </button>
            </div>
          ) : (
            <div className="grid grid-cols-2 gap-2.5">
              <button type="button" onClick={() => fotoRef.current?.click()} className="flex h-11 items-center justify-center gap-2 rounded-xl text-sm font-medium" style={inputBorder}>
                <Camera size={17} /> Tomar foto
              </button>
              <button type="button" onClick={() => galeriaRef.current?.click()} className="flex h-11 items-center justify-center gap-2 rounded-xl text-sm font-medium" style={inputBorder}>
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

  if (paso === 'item') {
    return (
      <BottomSheet onClose={onClose}>
        <button type="button" onClick={() => setPaso('resumen')} className="mb-2 flex items-center gap-1 text-sm font-medium text-app-muted">
          <CaretLeft size={16} /> Volver
        </button>
        <h2 className="text-lg font-semibold tracking-[-0.02em]">
          {presupuestoExistente ? 'Agregar hallazgo adicional' : 'Agregar ítem'}
        </h2>

        <div className="mt-3 flex gap-2">
          {TIPOS.map((t) => (
            <button
              key={t.value}
              type="button"
              onClick={() => {
                setTipo(t.value)
                setProductoSeleccionado(null)
                setPrecioUnitario('')
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
              <div className="flex items-center gap-3 rounded-xl bg-app-surface-2 p-2.5" style={inputBorder}>
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
                <button type="button" onClick={() => setProductoSeleccionado(null)} className="flex size-7 items-center justify-center rounded-full bg-app-surface-3">
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
                    style={inputBorder}
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
                        style={inputBorder}
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
            style={inputBorder}
          />
          <div className="grid grid-cols-2 gap-2.5">
            <input value={cantidad} onChange={(e) => setCantidad(e.target.value)} type="number" inputMode="decimal" placeholder="Cantidad" className={inputCls} style={inputBorder} />
            <input
              value={precioUnitario}
              onChange={(e) => setPrecioUnitario(e.target.value)}
              type="number"
              inputMode="decimal"
              placeholder="Precio unitario (Bs)"
              className={inputCls}
              style={inputBorder}
            />
          </div>
        </div>

        {error && <p className="mt-3 text-xs text-cor">{error}</p>}

        <button
          type="button"
          disabled={enviando}
          onClick={presupuestoExistente ? handleAgregarAdicional : agregarItemALaLista}
          className="mt-5 h-[54px] w-full rounded-xl bg-lime-500 text-[15px] font-semibold text-lime-ink disabled:opacity-60"
          style={{ boxShadow: 'var(--shadow-cta-lime)' }}
        >
          {presupuestoExistente ? (enviando ? 'Enviando…' : 'Enviar adicional al cliente') : 'Agregar a la lista'}
        </button>
      </BottomSheet>
    )
  }

  return (
    <BottomSheet onClose={onClose}>
      <h2 className="text-lg font-semibold tracking-[-0.02em]">Presupuesto</h2>

      {presupuestoExistente ? (
        <>
          <div className="mt-3 rounded-xl bg-app-surface-2 p-3.5" style={inputBorder}>
            <p className="text-[13px] font-medium">
              Ya existe un presupuesto v{presupuestoExistente.version} —{' '}
              <span className="text-app-muted">{presupuestoExistente.estado}</span>
            </p>
            <p className="mt-0.5 font-mono text-[15px] font-semibold">Bs {presupuestoExistente.total}</p>
            <p className="mt-1.5 text-[12px] text-app-muted">
              Si encontraste un problema nuevo durante el diagnóstico o la reparación, agrégalo como adicional para que el
              cliente lo apruebe por separado.
            </p>
          </div>
          <button
            type="button"
            onClick={() => setPaso('item')}
            className="mt-3.5 h-[54px] w-full rounded-xl bg-lime-500 text-[15px] font-semibold text-lime-ink"
            style={{ boxShadow: 'var(--shadow-cta-lime)' }}
          >
            + Agregar hallazgo adicional
          </button>
        </>
      ) : (
        <>
          <p className="mt-1 text-[13px] text-app-muted">
            Esta OT todavía no tiene presupuesto. Arma la lista de repuestos y trabajo, y envíalo al cliente para que dé el
            visto bueno antes de empezar.
          </p>

          {items.length > 0 && (
            <div className="mt-3 flex flex-col gap-2">
              {items.map((item) => (
                <div key={item.key} className="flex items-center gap-3 rounded-xl bg-app-surface-2 p-3" style={inputBorder}>
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-[13.5px] font-medium">{item.productoNombre ?? item.descripcion}</p>
                    <p className="text-[11px] text-app-faint">
                      {item.cantidad} × Bs {item.precio_unitario}
                    </p>
                  </div>
                  <p className="shrink-0 font-mono text-[13px]">Bs {(item.cantidad * item.precio_unitario).toFixed(2)}</p>
                  <button
                    type="button"
                    onClick={() => setItems((prev) => prev.filter((i) => i.key !== item.key))}
                    className="flex size-7 shrink-0 items-center justify-center rounded-full bg-app-surface-3"
                  >
                    <Trash size={14} />
                  </button>
                </div>
              ))}
              <div className="mt-1 flex items-center justify-between rounded-xl bg-app-surface-2 p-3">
                <p className="text-sm font-medium">Total</p>
                <p className="font-mono text-[17px] font-semibold">Bs {total.toFixed(2)}</p>
              </div>
            </div>
          )}

          <button
            type="button"
            onClick={() => setPaso('item')}
            className="mt-3 flex h-12 w-full items-center justify-center gap-2 rounded-xl text-sm font-medium text-lime-txt"
            style={{ border: '1px dashed var(--color-lime-500)' }}
          >
            + Agregar ítem
          </button>

          {error && <p className="mt-3 text-xs text-cor">{error}</p>}

          <button
            type="button"
            disabled={enviando || items.length === 0}
            onClick={handleEnviarPresupuesto}
            className="mt-3.5 h-[54px] w-full rounded-xl bg-lime-500 text-[15px] font-semibold text-lime-ink disabled:opacity-60"
            style={{ boxShadow: 'var(--shadow-cta-lime)' }}
          >
            {enviando ? 'Enviando…' : 'Enviar presupuesto al cliente'}
          </button>
        </>
      )}
    </BottomSheet>
  )
}
