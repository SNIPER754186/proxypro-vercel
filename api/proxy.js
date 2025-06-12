// api/proxy.js
export default async function handler(req, res) {
    const videoUrl = req.query.url;
  
    if (!videoUrl || !videoUrl.startsWith("http://vod.tuxchannel.mx/")) {
      return res.status(403).send("❌ URL inválida o dominio no permitido.");
    }
  
    try {
      const response = await fetch(videoUrl, {
        method: 'GET',
        headers: {
          'User-Agent': 'Mozilla/5.0',
        }
      });
  
      if (!response.ok) {
        return res.status(502).send("❌ No se pudo acceder al video.");
      }
  
      res.setHeader('Content-Type', response.headers.get('content-type') || 'application/octet-stream');
      res.setHeader('Access-Control-Allow-Origin', '*');
      res.setHeader('Access-Control-Allow-Headers', '*');
      res.setHeader('Access-Control-Allow-Methods', '*');
  
      response.body.pipe(res); // Transmisión directa
    } catch (err) {
      res.status(500).send("❌ Error interno del proxy.");
    }
  }
  