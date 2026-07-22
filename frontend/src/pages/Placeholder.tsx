import { Construction } from 'lucide-react'
import { Card, PageHeader } from '../components/ui'

export default function Placeholder({ title, note }: { title: string; note: string }) {
  return (
    <div>
      <PageHeader title={title} />
      <Card className="flex flex-col items-center justify-center gap-3 p-16 text-center">
        <Construction size={28} className="text-ink-faint" />
        <p className="max-w-md text-sm text-ink-muted">{note}</p>
      </Card>
    </div>
  )
}
