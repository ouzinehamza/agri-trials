import {useState} from 'react';
import {Head,Link,useForm} from '@inertiajs/react';
import {LayoutGrid,MapPin,Plus,Table2,User,X} from 'lucide-react';
import AppLayout from '@/Layouts/AppLayout'; import {Badge,Button,Card,PageHeader} from '@/Components/ui'; import {cn} from '@/lib/cn'; import DataTable,{type Column} from '@/Components/DataTable';
type Trial={id:number;code:string;variety:string;culture:string;conduct:string|null;owner:string|null;site:string|null;status:string;status_tone:'success'|'warning'|'info'|'neutral'|'danger';workspace:string|null};
type Template={id:number;name:Record<string,string>;workflow:{name:Record<string,string>};measurement_set:{name:Record<string,string>}}; type Workspace={id:number;name:string}; const fr=(v:Record<string,string>)=>v.fr??Object.values(v)[0]??''; const input='w-full rounded-md border border-line bg-page px-3 py-2 text-sm outline-none focus:border-brand';
export default function Index({trials,templates,workspaces}:{trials:Trial[];templates:Template[];workspaces:Workspace[]}){
  const[create,setCreate]=useState(false);
  const[view,setView]=useState<'cards'|'table'>(()=>(localStorage.getItem('agri-trials-view') as 'cards'|'table')||'cards');
  const setV=(v:'cards'|'table')=>{setView(v);localStorage.setItem('agri-trials-view',v)};
  const statuses=[...new Set(trials.map(t=>t.status))];
  const spaces=[...new Set(trials.map(t=>t.workspace).filter(Boolean) as string[])];
  const columns:Column<Trial>[]=[
    {key:'code',label:'Code',value:t=>t.code,render:t=><Link href={`/trials/${t.id}`} className="font-medium text-ink hover:text-brand">{t.code}</Link>},
    {key:'variety',label:'Variété',value:t=>t.variety,render:t=><Link href={`/trials/${t.id}`} className="text-ink hover:text-brand">{t.variety}</Link>},
    {key:'culture',label:'Culture',value:t=>t.culture},
    {key:'conduct',label:'Conduite',value:t=>t.conduct||'—'},
    {key:'owner',label:'Responsable',value:t=>t.owner||'—'},
    {key:'site',label:'Site',value:t=>t.site||'—'},
    {key:'workspace',label:'Espace',filterType:'select',filterOptions:spaces,value:t=>t.workspace||'—',render:t=>t.workspace?<Badge>{t.workspace}</Badge>:<>—</>},
    {key:'status',label:'Statut',filterType:'select',filterOptions:statuses,value:t=>t.status,render:t=><Badge tone={t.status_tone}>{t.status}</Badge>},
  ];
  return <AppLayout><Head title="Essais"/>
    <PageHeader title="Essais" subtitle={`${trials.length} essais variétaux`} actions={<div className="flex items-center gap-2">
      <div className="flex rounded-lg border border-line p-0.5">
        <button onClick={()=>setV('cards')} aria-label="Vue cartes" className={cn('rounded-md p-1.5 transition-colors',view==='cards'?'bg-surface-2 text-ink':'text-ink-faint hover:text-ink')}><LayoutGrid size={16}/></button>
        <button onClick={()=>setV('table')} aria-label="Vue tableau" className={cn('rounded-md p-1.5 transition-colors',view==='table'?'bg-surface-2 text-ink':'text-ink-faint hover:text-ink')}><Table2 size={16}/></button>
      </div>
      <Button variant="primary" onClick={()=>setCreate(true)}><Plus size={16}/> Nouvel essai</Button>
    </div>}/>
    {view==='table'
      ? <DataTable id="trials" rows={trials} rowKey={t=>t.id} pageSize={25} columns={columns}/>
      : <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">{trials.map(t=><Link key={t.id} href={`/trials/${t.id}`}><Card hover className="h-full p-5"><div className="flex justify-between"><div><div className="font-medium">{t.variety}</div><div className="text-xs text-ink-faint">{t.code}</div></div><Badge tone={t.status_tone}>{t.status}</Badge></div><div className="mt-3 text-xs text-ink-muted">{t.culture}</div>{t.workspace&&<div className="mt-2"><Badge>{t.workspace}</Badge></div>}<div className="mt-4 flex justify-between border-t border-line pt-3 text-xs text-ink-faint"><span className="flex gap-1"><User size={13}/>{t.owner}</span><span className="flex gap-1"><MapPin size={13}/>{t.site}</span></div></Card></Link>)}</div>}
    {create&&<CreateTrial templates={templates} workspaces={workspaces} close={()=>setCreate(false)}/>}
  </AppLayout>;
}
function CreateTrial({templates,workspaces,close}:{templates:Template[];workspaces:Workspace[];close:()=>void}){const f=useForm({code:'P'+String(Date.now()).slice(-5),trial_template_id:templates[0]?.id??0,workspace_id:workspaces[0]?.id??0,variety:'',culture:'',conduct:'',supplier:'',segment:'',site:'',season:String(new Date().getFullYear()),controlsText:''});const submit=(e:React.FormEvent)=>{e.preventDefault();f.transform((d:any)=>({...d,controls:d.controlsText.split(',').map((x:string)=>x.trim()).filter(Boolean)}));f.post('/trials',{onSuccess:close})};return <div className="fixed inset-0 z-50 flex items-start justify-center overflow-auto bg-ink/40 p-5 animate-fade-in"><Card className="w-full max-w-2xl p-5 animate-scale-in"><div className="mb-5 flex justify-between"><div><h2 className="text-lg font-medium">Nouvel essai</h2><p className="text-sm text-ink-muted">Le workflow et les mesures seront copiés et figés à la création.</p></div><button onClick={close} aria-label="Fermer"><X size={18}/></button></div><form onSubmit={submit} className="grid grid-cols-2 gap-4"><label className="text-sm">Code<input className={input} value={f.data.code} onChange={e=>f.setData('code',e.target.value)}/></label><label className="text-sm">Espace<select className={input} value={f.data.workspace_id} onChange={e=>f.setData('workspace_id',+e.target.value)}>{workspaces.map(w=><option key={w.id} value={w.id}>{w.name}</option>)}</select></label><label className="col-span-2 text-sm">Modèle d’essai<select className={input} value={f.data.trial_template_id} onChange={e=>f.setData('trial_template_id',+e.target.value)}>{templates.map(t=><option key={t.id} value={t.id}>{fr(t.name)} — {fr(t.workflow.name)} / {fr(t.measurement_set.name)}</option>)}</select></label>{[['variety','Variété'],['culture','Culture'],['conduct','Conduite'],['supplier','Fournisseur'],['segment','Segment'],['site','Site'],['season','Campagne']] .map(([k,l])=><label key={k} className="text-sm">{l}<input className={input} value={(f.data as any)[k]} onChange={e=>f.setData(k as any,e.target.value)}/></label>)}<label className="col-span-2 text-sm">Témoins (séparés par des virgules)<input className={input} value={f.data.controlsText} onChange={e=>f.setData('controlsText',e.target.value)}/></label>{Object.values(f.errors).length>0&&<div className="col-span-2 rounded-md bg-danger-soft p-3 text-sm text-danger">{Object.values(f.errors)[0]}</div>}<div className="col-span-2 flex justify-end"><Button type="submit" variant="primary">Créer depuis le modèle</Button></div></form></Card></div>}
