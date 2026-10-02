import http from 'http';
import fs from 'fs';

const server = http.createServer((req, res) => {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');
  if (req.method === 'OPTIONS') {
    res.writeHead(200);
    res.end();
    return;
  }
  if (req.method === 'POST') {
    let body = '';
    req.on('data', chunk => { body += chunk; });
    req.on('end', () => {
      try {
        const data = JSON.parse(body);
        const base64Data = data.base64.replace(/^data:image\/png;base64,/, '');
        const outPath = 'c:/Users/Ashu/Desktop/New folder (3)/Restaurant_system2/Client2/vue-project/public/images/Hotel logo.png';
        fs.writeFileSync(outPath, Buffer.from(base64Data, 'base64'));
        console.log('SUCCESS_WRITTEN_PNG');
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ success: true }));
        setTimeout(() => process.exit(0), 500);
      } catch (err) {
        console.error(err);
        res.writeHead(500);
        res.end(String(err));
      }
    });
  }
});

server.listen(9876, () => {
  console.log('SERVER_READY_9876');
});
