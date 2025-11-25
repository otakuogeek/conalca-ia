import AsyncSelect from 'react-select/async';
export default function SearchSelect({label,load, onChange, value, getOpt}){
  const loadOpts = (input, cb) =>
      load(input).then(r=>cb(r.data.map(getOpt)));
  return (
    <div className="space-y-1">
      <span className="text-sm">{label}</span>
      <AsyncSelect cacheOptions
                   defaultOptions
                   loadOptions={loadOpts}
                   value={value}
                   onChange={onChange}/>
    </div>);
}