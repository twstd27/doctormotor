export default function PrivacidadPage() {
  return (
    <div className="min-h-svh bg-app-bg px-5 py-10 text-app-text">
      <div className="mx-auto max-w-[720px]">
        <p className="font-mono text-[11px] tracking-[0.04em] text-app-faint">DOCTOR MOTOR · MUSTANG'S GARAGE</p>
        <h1 className="mt-1 text-[28px] font-semibold tracking-[-0.02em]">Política de privacidad</h1>
        <p className="mt-1 text-sm text-app-muted">Última actualización: 18 de septiembre de 2026</p>

        <div className="mt-6 flex flex-col gap-6 text-[14.5px] leading-[1.65] text-app-text">
          <p>
            Esta política explica qué información recopila Doctor Motor a través de su sistema de gestión
            (panel administrativo y aplicación web para clientes y técnicos) y cómo la usamos.
          </p>

          <section>
            <h2 className="text-[16px] font-semibold text-lime-txt">Qué información recopilamos</h2>
            <ul className="mt-2 list-disc space-y-1.5 pl-5 text-app-muted">
              <li>Datos de contacto: nombre, número de WhatsApp y, si lo proporcionas, correo electrónico y CI/NIT.</li>
              <li>Datos del vehículo: placa, marca, modelo, año, color y kilometraje.</li>
              <li>Información del servicio: descripción del problema, inspección de ingreso, fotos y videos del vehículo, firma digital de recepción, presupuestos y su aprobación, y el historial de cada orden de trabajo.</li>
              <li>Pagos: monto, método y fecha de los cobros asociados a tu vehículo (no almacenamos datos de tarjetas).</li>
              <li>
                Si inicias sesión con Google, recibimos tu nombre y correo asociados a esa cuenta. Si accedes
                por WhatsApp, usamos el número desde el que escribes para identificarte.
              </li>
            </ul>
          </section>

          <section>
            <h2 className="text-[16px] font-semibold text-lime-txt">Para qué usamos esta información</h2>
            <ul className="mt-2 list-disc space-y-1.5 pl-5 text-app-muted">
              <li>Gestionar la recepción, diagnóstico, reparación y entrega de tu vehículo.</li>
              <li>Enviarte actualizaciones sobre el estado de tu orden de trabajo y presupuestos por WhatsApp.</li>
              <li>Registrar cobros y llevar el historial de servicio de tu vehículo.</li>
              <li>Permitirte consultar, desde tu cuenta, el estado y el historial de tus vehículos.</li>
            </ul>
          </section>

          <section>
            <h2 className="text-[16px] font-semibold text-lime-txt">Con quién compartimos información</h2>
            <p className="mt-2 text-app-muted">
              No vendemos ni compartimos tus datos con terceros para fines de publicidad. Usamos los
              siguientes servicios únicamente para operar el sistema:
            </p>
            <ul className="mt-2 list-disc space-y-1.5 pl-5 text-app-muted">
              <li><strong className="text-app-text">Meta (WhatsApp Business Cloud API)</strong> — para enviarte notificaciones sobre tu orden de trabajo.</li>
              <li><strong className="text-app-text">Google</strong> — únicamente si eliges iniciar sesión con tu cuenta de Google.</li>
              <li><strong className="text-app-text">DigitalOcean</strong> — nuestro proveedor de hosting y almacenamiento en la nube, donde se guardan de forma segura los datos y las fotos/videos de evidencia.</li>
            </ul>
          </section>

          <section>
            <h2 className="text-[16px] font-semibold text-lime-txt">Tus derechos</h2>
            <p className="mt-2 text-app-muted">
              Puedes solicitar en cualquier momento acceder a tus datos, corregirlos o pedir que los
              eliminemos, escribiéndonos por WhatsApp al número del taller.
            </p>
          </section>

          <section>
            <h2 className="text-[16px] font-semibold text-lime-txt">Contacto</h2>
            <p className="mt-2 text-app-muted">
              Para cualquier consulta sobre esta política o tus datos, contáctanos por WhatsApp al número
              oficial de Doctor Motor.
            </p>
          </section>
        </div>
      </div>
    </div>
  )
}
