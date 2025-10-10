import { useEffect, useRef } from 'react';

export default function useInterval(callback, delay) {
  const savedCallback = useRef();

  // Guarda último callback
  useEffect(() => { savedCallback.current = callback; }, [callback]);

  // Configura el interval
  useEffect(() => {
    if (delay === null) return;
    const id = setInterval(() => savedCallback.current(), delay);
    return () => clearInterval(id);
  }, [delay]);
}