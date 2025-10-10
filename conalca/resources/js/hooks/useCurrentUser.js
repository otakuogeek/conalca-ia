import { useEffect, useState } from 'react';
import { getCurrentUser }        from '../services/userService';

export default function useCurrentUser(){
  const [user,setUser] = useState(null);
  const [loading,setLoading] = useState(true);

  useEffect(()=>{
    getCurrentUser()
      .then(r=>setUser(r.data))
      .finally(()=>setLoading(false));
  },[]);

  return { user, loading };
}