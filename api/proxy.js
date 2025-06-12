export default async function handler(req, res) {
  const { url } = req.query;

  if (!url) {
    res.status(400).send("❌ Parámetro 'url' requerido.");
    return;
  }

  const allowedDomain = "vod.tuxchannel.mx";
  try {
    const targetUrl = new URL(url);
    if (!targetUrl.hostname.endsWith(allowedDomain)) {
      return res.status(403).send("❌ Dominio no permitido.");
    }
  } catch (err) {
    return res.status(400).send("❌ URL inválida.");
  }

  try {
    const response = await fetch(url);
    if (!response.ok) throw new Error("❌ No se pudo acceder al video.");

    res.setHeader("Content-Type", response.headers.get("content-type") || "application/octet-stream");
    res.setHeader("Access-Control-Allow-Origin", "*");

    response.body.pipe(res);
  } catch (error) {
    res.status(500).send(error.message || "❌ Error al obtener el archivo.");
  }
}
