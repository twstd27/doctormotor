import { CheckCircle } from '@phosphor-icons/react'
import { useToastStore } from '../store/toast'

export default function Toast() {
  const message = useToastStore((s) => s.message)
  if (!message) return null

  return (
    <div className="dm-in fixed inset-x-0 bottom-[104px] z-[80] flex justify-center px-4">
      <div
        className="flex items-center gap-2 rounded-full bg-app-surface px-4 py-2.5 text-[13px] font-medium text-app-text"
        style={{ boxShadow: 'var(--shadow-toast)' }}
      >
        <CheckCircle weight="fill" size={18} className="text-lime-500" />
        {message}
      </div>
    </div>
  )
}
