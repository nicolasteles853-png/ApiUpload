const express = require('express');
const multer = require('multer');
const cors = require('cors');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 3000;

app.set('trust proxy', 1);

app.use(cors());

app.use(express.json({
    limit: '100mb'
}));

app.use(express.urlencoded({
    extended: true,
    limit: '100mb'
}));

const uploadDir = process.env.VERCEL
? '/tmp/Uploads'
: path.join(__dirname, 'Uploads');

try {

    if (!fs.existsSync(uploadDir)) {

        fs.mkdirSync(uploadDir, {
            recursive: true
        });

    }

} catch (e) {}

app.use('/Uploads', express.static(uploadDir));

const storage = multer.diskStorage({

    destination: (req, file, cb) => {

        cb(null, uploadDir);

    },

    filename: (req, file, cb) => {

        const timestamp = Date.now();

        const cleanName = file.originalname
        .replace(/\s+/g, '_')
        .replace(/[^\w.\-]/g, '');

        cb(null, timestamp + '-' + cleanName);

    }

});

const upload = multer({

    storage: storage,

    limits: {
        fileSize: 100 * 1024 * 1024
    }

});

function getBaseUrl(req) {

    const protocol =
    req.headers['x-forwarded-proto']
    || req.protocol
    || 'http';

    return protocol + '://' + req.get('host');

}

app.get('/', (req, res) => {

    res.status(200).json({
        success: true,
        message: 'API Online'
    });

});

app.post('/upload', (req, res, next) => {

    upload.single('file')(req, res, function(err) {

        if (err) {

            if (err instanceof multer.MulterError) {

                return res.status(400).json({
                    success: false,
                    message: err.message
                });

            }

            return res.status(500).json({
                success: false,
                error: err.message
            });

        }

        next();

    });

}, (req, res) => {

    try {

        if (!req.file) {

            return res.status(400).json({
                success: false,
                message: 'Nenhum arquivo enviado'
            });

        }

        const baseUrl = getBaseUrl(req);

        const fileUrl = baseUrl + '/Uploads/' + req.file.filename;

        res.status(200).json({

            success: true,

            status: 200,

            message: 'Upload realizado com sucesso',

            file: {

                name: req.file.originalname,
                saved: req.file.filename,
                size: req.file.size,
                type: req.file.mimetype,
                extension: path.extname(req.file.filename),
                url: fileUrl

            },

            server: {

                host: req.hostname,
                ip: req.ip,
                protocol: req.protocol

            },

            timestamp: new Date().toISOString()

        });

    } catch (error) {

        res.status(500).json({
            success: false,
            error: error.message
        });

    }

});

app.get('/files', (req, res) => {

    try {

        fs.readdir(uploadDir, (err, files) => {

            if (err) {

                return res.status(500).json({
                    success: false,
                    error: err.message
                });

            }

            const baseUrl = getBaseUrl(req);

            const list = [];

            for (let i = 0; i < files.length; i++) {

                list.push({

                    name: files[i],
                    url: baseUrl + '/Uploads/' + files[i]

                });

            }

            res.status(200).json({

                success: true,
                total: files.length,
                files: list

            });

        });

    } catch (e) {

        res.status(500).json({
            success: false,
            error: e.message
        });

    }

});

app.use((req, res) => {

    res.status(404).json({
        success: false,
        error: 'Rota não encontrada'
    });

});

app.use((err, req, res, next) => {

    if (err instanceof multer.MulterError) {

        return res.status(400).json({
            success: false,
            message: err.message
        });

    }

    res.status(500).json({
        success: false,
        error: err.message || 'Erro interno'
    });

});

if (!process.env.VERCEL) {

    app.listen(PORT, () => {

        console.log('Rodando em http://localhost:' + PORT);

    });

}

module.exports = app;
