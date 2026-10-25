import sharp from 'sharp';
import { mkdirSync, readFileSync } from 'node:fs';

mkdirSync('public/icons', { recursive: true });

const rounded  = readFileSync('resources/icons/icon.svg');
const fullBleed = readFileSync('resources/icons/icon-maskable.svg');

const jobs = [
    [rounded,   192, 'icon-192.png'],
    [rounded,   512, 'icon-512.png'],
    [rounded,    32, 'favicon-32.png'],
    [fullBleed, 512, 'maskable-512.png'],
    [fullBleed, 180, 'apple-touch-icon.png'],
];

for (const [source, size, name] of jobs) {
    await sharp(source, { density: 384 }).resize(size, size).png().toFile(`public/icons/${name}`);
    console.log('created public/icons/' + name);
}