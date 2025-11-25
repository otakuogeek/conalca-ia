import React,{useEffect,useState,useRef} from 'react';
import dayjs from 'dayjs';
import { fetchMessages, createMessage } from '../../services/solicitations';
import { FiSend } from 'react-icons/fi';

export default function ChatBox({ solicitationId, channel, title }) {
const [messages,setMessages] = useState([]);
const [content,setContent]   = useState('');
const bottomRef              = useRef();

const getData = async () => {
const res = await fetchMessages(solicitationId,channel);
setMessages(res.data);
bottomRef.current?.scrollIntoView({behavior:'smooth'});
};

useEffect(()=>{getData();},[solicitationId,channel]);
useEffect(()=>{
const id=setInterval(getData,5000);
return ()=>clearInterval(id);
},[channel]);

const send = async e => {
e.preventDefault();
if(!content.trim()) return;
await createMessage(solicitationId,channel,content);
setContent('');
getData();
};

return (
<div className="border-t mt-6 pt-4">
<h4 className="font-semibold mb-3 text-[#FF7C32]">{title}</h4>

  <div className="h-56 overflow-y-auto bg-gray-50 border p-3 rounded-lg mb-3">
    {messages.length===0 &&
      <p className="text-xs text-gray-400 text-center mt-6 select-none">
        No hay mensajes aún.
      </p>}
    {messages.map(m=>(
      <div key={m.id} className="mb-3">
        <div className="inline-block bg-white border rounded-lg px-3 py-1.5 text-sm shadow">
          <span className="font-bold text-[#FF7C32]">{m.user.name}</span>{" "}
          <span className="text-gray-700 break-words">{m.content}</span>
        </div>
        <div className="text-[11px] text-gray-400 ml-2">
          {dayjs(m.created_at).format('DD/MM HH:mm')}
        </div>
      </div>
    ))}
    <div ref={bottomRef} />
  </div>

  <form onSubmit={send} className="flex gap-2">
    <input
      value={content}
      onChange={e=>setContent(e.target.value)}
      className="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-[#FF7C32] outline-none"
      placeholder="Escribe un mensaje..."
      maxLength={400}
    />
    <button
      disabled={!content.trim()}
      className="bg-[#FF7C32] hover:bg-[#ff974e] text-white px-4 rounded-md flex items-center justify-center disabled:opacity-70"
    >
      <FiSend className="text-lg"/>
    </button>
  </form>
</div>
);
}