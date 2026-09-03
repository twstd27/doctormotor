import { X } from '@phosphor-icons/react'
import type { ReactNode } from 'react'

interface BottomSheetProps {
  onClose: () => void
  children: ReactNode
}

export default function BottomSheet({ onClose, children }: BottomSheetProps) {
  return (
    <div
      className="fixed inset-0 z-[60] flex items-end justify-center"
      style={{ background: 'rgba(6,8,10,.5)', backdropFilter: 'blur(2px)', animation: 'dmFade .22s ease' }}
      onClick={onClose}
    >
      <div
        className="w-full overflow-y-auto bg-app-surface"
        style={{
          maxWidth: 520,
          maxHeight: '88vh',
          borderRadius: '20px 20px 0 0',
          boxShadow: 'var(--shadow-sheet)',
          animation: 'dmUp .3s cubic-bezier(.32,.72,0,1)',
        }}
        onClick={(e) => e.stopPropagation()}
      >
        <div className="sticky top-0 z-10 flex justify-center bg-app-surface pt-2.5 pb-1">
          <span className="h-[5px] w-[38px] rounded-full" style={{ background: 'var(--color-app-line)' }} />
        </div>
        <button
          type="button"
          onClick={onClose}
          className="absolute top-3 right-4 flex size-8 items-center justify-center rounded-full bg-app-surface-3"
        >
          <X size={16} />
        </button>
        <div className="px-5 pt-1 pb-6">{children}</div>
      </div>
    </div>
  )
}
