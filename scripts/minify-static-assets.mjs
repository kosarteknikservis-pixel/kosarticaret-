import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { dirname } from 'node:path';
import { transform } from 'esbuild';

const assets = [
    { source: 'public/css/shop.css', target: 'public/css/shop.min.css', loader: 'css' },
    { source: 'public/js/shop.js', target: 'public/js/shop.min.js', loader: 'js' },
];

for (const asset of assets) {
    const source = await readFile(asset.source, 'utf8');
    const result = await transform(source, {
        loader: asset.loader,
        minify: true,
        legalComments: 'none',
    });

    await mkdir(dirname(asset.target), { recursive: true });
    await writeFile(asset.target, result.code, 'utf8');
    console.log(`minified ${asset.source} -> ${asset.target}`);
}
