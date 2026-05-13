(function () {
    const params = new URLSearchParams(window.location.search);
    const currentView = params.get('view') || 'generator';
    const table = document.getElementById('resultTable');
    const previewCanvas = document.getElementById('previewCanvas');
    const emptyPreview = document.getElementById('emptyPreview');
    const downloadSelected = document.getElementById('downloadSelected');
    const downloadZip = document.getElementById('downloadZip');
    const downloadPdf = document.getElementById('downloadPdf');
    const downloadErrorCsv = document.getElementById('downloadErrorCsv');
    const pdfPerPage = document.getElementById('pdfPerPage');
    const barcodeType = document.getElementById('barcodeType');
    const navButtons = document.querySelectorAll('button[data-bcid]');
    const symGroupToggles = document.querySelectorAll('.sym-group-toggle');
    const symbologyToggle = document.getElementById('symbologyToggle');
    const symbologyList = document.getElementById('symbologyList');
    const inputText = document.getElementById('input_text');
    const inputFile = document.getElementById('inputFile');
    const dropzone = document.querySelector('.dropzone');
    const lineCount = document.getElementById('lineCount');
    const sampleDataBtn = document.getElementById('sampleDataBtn');
    const clearInputBtn = document.getElementById('clearInputBtn');
    const workspaceViewContent = {
        jobs: {
            eyebrow: 'Batch Jobs',
            title: 'Batch jobs queue',
            description: 'Monitor uploads, queue progress, export packages and recovery status for large production runs.',
            cards: [
                ['Queue intake', 'Upload CSV or TXT batches, validate rows before processing and track acceptance in one place.'],
                ['Runtime visibility', 'Operators can review pending, processing and failed jobs without leaving the workspace.'],
                ['Recovery flow', 'Retry failed runs, download valid subsets and keep the line moving during peak volume.'],
            ],
            tableTitle: 'Recent batch jobs',
            tableAction: 'New batch upload',
            rowAction: 'Retry job',
        },
        history: {
            eyebrow: 'History',
            title: 'Generation history',
            description: 'Review previous barcode exports, reopen successful runs and track what was delivered to operations teams.',
            cards: [
                ['Searchable records', 'Locate prior jobs by barcode type, date and export format when teams ask for a resend.'],
                ['Operator audit', 'Keep a clean timeline of generated labels, row counts and download actions.'],
                ['Fast recovery', 'Reopen recent outputs instead of regenerating the same asset package from scratch.'],
            ],
            tableTitle: 'Recent exports',
            tableAction: 'Export history CSV',
            rowAction: 'Open package',
        },
        validation: {
            eyebrow: 'Validation Reports',
            title: 'Validation reports',
            description: 'Track invalid rows, formatting mismatches and GS1 normalization issues before they hit production.',
            cards: [
                ['Error spotting', 'See invalid data patterns early and keep bad rows out of print and shipment workflows.'],
                ['GS1 readiness', 'Normalization and compliance notes help teams fix structured data faster.'],
                ['Downloadable reports', 'Share CSV-based issue lists with support, packaging or ERP teams.'],
            ],
            tableTitle: 'Recent validation runs',
            tableAction: 'Export issue report',
            rowAction: 'View errors',
        },
        templates: {
            eyebrow: 'Templates',
            title: 'Saved templates',
            description: 'Store repeatable barcode presets for packaging lines, retailer requirements and internal labeling standards.',
            cards: [
                ['Preset stacks', 'Save symbology, scale and export preferences for recurring production jobs.'],
                ['Team consistency', 'Give every operator the same output rules across shifts and workstations.'],
                ['Faster launch', 'Start from a known-good template instead of rebuilding settings every time.'],
            ],
            tableTitle: 'Template activity',
            tableAction: 'Create template',
            rowAction: 'Use template',
        },
        api: {
            eyebrow: 'API Keys',
            title: 'API access and webhooks',
            description: 'Manage machine-to-machine access, webhook delivery and usage posture for integrated barcode workflows.',
            cards: [
                ['API credentials', 'Issue environment-specific keys for ERP, WMS and automation pipelines.'],
                ['Webhook events', 'Notify downstream systems when batch jobs complete, fail or need review.'],
                ['Audit posture', 'Track integration usage and keep operational access scoped and visible.'],
            ],
            tableTitle: 'Integration activity',
            tableAction: 'Create API key',
            rowAction: 'Rotate key',
        },
        team: {
            eyebrow: 'Team Members',
            title: 'Team and permissions',
            description: 'Control who can generate, export, manage integrations and review billing inside the workspace.',
            cards: [
                ['Role-based access', 'Separate operator, manager and admin capabilities across the product surface.'],
                ['Shared workspace', 'Keep branding, templates and export history aligned across the whole team.'],
                ['Operational handoff', 'Make shift changes easier with consistent access and visible ownership.'],
            ],
            tableTitle: 'Member activity',
            tableAction: 'Invite member',
            rowAction: 'Manage role',
        },
        settings: {
            eyebrow: 'Settings',
            title: 'Workspace settings',
            description: 'Configure workspace defaults, export behavior and account-level preferences for daily barcode operations.',
            cards: [
                ['Generation defaults', 'Set default symbologies, output scale and PDF behavior for faster operator setup.'],
                ['Branding and company', 'Manage company profile, white-label settings and support contact details.'],
                ['Workflow preferences', 'Control how teams handle validation, downloads and repeated production tasks.'],
            ],
            tableTitle: 'Settings change history',
            tableAction: 'Save preferences',
            rowAction: 'Review',
        },
        support: {
            eyebrow: 'Support',
            title: 'Support center',
            description: 'Help operators resolve barcode issues quickly with guided support, status visibility and documentation links.',
            cards: [
                ['Guided troubleshooting', 'Surface common fixes for invalid rows, export issues and batch processing interruptions.'],
                ['Priority support', 'Route urgent operational blockers with the right context attached.'],
                ['Team handoff', 'Give support and operations a shared view of the same recent runs and error patterns.'],
            ],
            tableTitle: 'Recent support-related activity',
            tableAction: 'Contact support',
            rowAction: 'Open case',
        },
    };

    function applyWorkspaceViewContent() {
        const content = workspaceViewContent[currentView];
        if (!content) {
            return;
        }

        const pageHead = document.querySelector('.page-head');
        const cards = document.querySelectorAll('.placeholder-grid .card');
        const tableCard = document.querySelector('.table-card');
        if (!pageHead || !cards.length || !tableCard) {
            return;
        }

        const eyebrow = pageHead.querySelector('.eyebrow');
        const title = pageHead.querySelector('h1');
        const description = pageHead.querySelector('p:not(.eyebrow)');
        if (eyebrow) eyebrow.textContent = content.eyebrow;
        if (title) title.textContent = content.title;
        if (description) description.textContent = content.description;

        cards.forEach((card, index) => {
            const cardContent = content.cards[index];
            if (!cardContent) {
                return;
            }
            const cardTitle = card.querySelector('h2');
            const cardText = card.querySelector('p');
            if (cardTitle) cardTitle.textContent = cardContent[0];
            if (cardText) cardText.textContent = cardContent[1];
        });

        const tableTitle = tableCard.querySelector('.card-head h2');
        const tableAction = tableCard.querySelector('.card-head .btn');
        const rowButtons = tableCard.querySelectorAll('.mini-btn');
        if (tableTitle) tableTitle.textContent = content.tableTitle;
        if (tableAction) tableAction.textContent = content.tableAction;
        rowButtons.forEach((button) => {
            button.textContent = content.rowAction;
        });
    }

    applyWorkspaceViewContent();

    function countDataLines(value) {
        return String(value || '')
            .split(/\r?\n/)
            .map((line) => line.trim())
            .filter(Boolean).length;
    }

    function updateLineCount() {
        if (lineCount && inputText) {
            lineCount.textContent = String(countDataLines(inputText.value));
        }
    }

    function safeName(value) {
        return String(value).replace(/[^a-z0-9_-]+/gi, '_').replace(/^_+|_+$/g, '') || 'barcode';
    }

    function downloadText(filename, content, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.click();
        URL.revokeObjectURL(url);
    }

    function syncBarcodeButtons(value) {
        navButtons.forEach((item) => {
            item.classList.toggle('active', (item.dataset.bcid || '') === value);
        });
    }

    navButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const nextValue = button.dataset.bcid || barcodeType?.value || '';

            if (!barcodeType) {
                const target = new URL('index.php', window.location.href);
                target.searchParams.set('view', 'generator');
                target.searchParams.set('barcode_type', nextValue);
                window.location.href = target.toString();
                return;
            }

            barcodeType.value = nextValue;
            syncBarcodeButtons(nextValue);
            if (!document.querySelector('.generator-grid')) {
                const target = new URL('index.php', window.location.href);
                target.searchParams.set('view', 'generator');
                target.searchParams.set('barcode_type', nextValue);
                window.location.href = target.toString();
            }
        });
    });

    barcodeType?.addEventListener('change', () => {
        syncBarcodeButtons(barcodeType.value);
    });

    if (barcodeType) {
        syncBarcodeButtons(barcodeType.value);
    }

    symGroupToggles.forEach((button) => {
        button.addEventListener('click', () => {
            const group = button.closest('.sym-group');
            const listId = button.getAttribute('aria-controls');
            const list = listId ? document.getElementById(listId) : null;
            if (!group || !list) {
                return;
            }

            const isOpen = button.getAttribute('aria-expanded') === 'true';
            button.setAttribute('aria-expanded', String(!isOpen));
            list.hidden = isOpen;
            group.classList.toggle('open', !isOpen);
            const icon = button.querySelector('b');
            if (icon) {
                icon.textContent = isOpen ? '+' : '-';
            }
        });
    });

    symbologyToggle?.addEventListener('click', () => {
        if (!symbologyList) {
            return;
        }

        const isOpen = symbologyToggle.getAttribute('aria-expanded') === 'true';
        symbologyToggle.setAttribute('aria-expanded', String(!isOpen));
        symbologyList.hidden = isOpen;
        const icon = symbologyToggle.querySelector('b');
        if (icon) {
            icon.textContent = isOpen ? '+' : '-';
        }
    });

    inputText?.addEventListener('input', updateLineCount);
    updateLineCount();

    sampleDataBtn?.addEventListener('click', () => {
        if (!inputText) {
            return;
        }

        inputText.value = [
            '011234567890123421ABC93XYZ',
            '(01)12345678901234(21)SERIAL001(91)A1(92)B2',
            'NORMAL-DATAMATRIX-001',
        ].join('\n');
        updateLineCount();
        inputText.focus();
    });

    clearInputBtn?.addEventListener('click', () => {
        if (!inputText) {
            return;
        }
        inputText.value = '';
        updateLineCount();
        inputText.focus();
    });

    if (dropzone && inputFile) {
        ['dragenter', 'dragover'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            dropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                dropzone.classList.remove('dragover');
            });
        });

        dropzone.addEventListener('drop', (event) => {
            if (event.dataTransfer?.files?.length) {
                inputFile.files = event.dataTransfer.files;
                const fileName = event.dataTransfer.files[0].name;
                const label = dropzone.querySelector('span');
                if (label) {
                    label.textContent = fileName;
                }
            }
        });

        inputFile.addEventListener('change', () => {
            const label = dropzone.querySelector('span');
            if (label && inputFile.files?.length) {
                label.textContent = inputFile.files[0].name;
            }
        });
    }

    if (!table || !previewCanvas) {
        return;
    }

    const bcid = table.dataset.bcid || 'gs1datamatrix';
    const scale = Number(table.dataset.scale || 4);
    const validRows = Array.from(table.querySelectorAll('tbody tr[data-valid="1"]'));
    const invalidRows = Array.from(table.querySelectorAll('tbody tr[data-valid="0"]'));
    let selectedRow = validRows[0] || null;

    const encoderAliases = {
        'gs1-128': 'gs1-128',
        'interleaved2of5': 'interleaved2of5',
        'azteccode': 'azteccode',
        'hibcazteccode': 'hibcazteccode',
        'hibcdatamatrix': 'hibcdatamatrix',
        'hibcqrcode': 'hibcqrcode',
    };

    function optionsFor(text) {
        const encoder = encoderAliases[bcid] || bcid;
        const options = {
            bcid: encoder,
            text,
            scale,
            paddingwidth: 8,
            paddingheight: 8,
            backgroundcolor: 'FFFFFF',
        };

        if (bcid === 'code128' || bcid === 'gs1-128' || bcid === 'ean13') {
            options.height = 18;
            options.includetext = true;
            options.textxalign = 'center';
        }

        if (bcid === 'qrcode' || bcid === 'gs1qrcode' || bcid === 'microqrcode') {
            options.eclevel = 'M';
        }

        if (bcid === 'datamatrix' || bcid === 'gs1datamatrix') {
            options.format = 'square';
        }

        if (bcid === 'gs1datamatrix' || bcid === 'gs1-128' || bcid === 'gs1qrcode') {
            options.dontlint = true;
        }

        return options;
    }

    function setSelected(row) {
        if (!row) {
            return;
        }

        validRows.forEach((item) => item.classList.remove('is-selected'));
        row.classList.add('is-selected');
        selectedRow = row;
        renderPreview(row.dataset.text || '');
    }

    function renderPreview(text) {
        try {
            bwipjs.toCanvas(previewCanvas, optionsFor(text));
            emptyPreview.style.display = 'none';
            downloadSelected.disabled = false;
            downloadZip.disabled = validRows.length === 0;
            downloadPdf.disabled = validRows.length === 0;
        } catch (error) {
            emptyPreview.textContent = String(error);
            emptyPreview.style.display = 'block';
            downloadSelected.disabled = true;
        }
    }

    function downloadCanvas(canvas, filename) {
        const link = document.createElement('a');
        link.download = filename;
        link.href = canvas.toDataURL('image/png');
        link.click();
    }

    validRows.forEach((row) => {
        row.addEventListener('click', () => setSelected(row));
    });

    if (selectedRow) {
        setSelected(selectedRow);
    }

    downloadSelected?.addEventListener('click', () => {
        if (!selectedRow) {
            return;
        }
        downloadCanvas(previewCanvas, `barcode_${safeName(selectedRow.dataset.line)}.png`);
    });

    downloadErrorCsv?.addEventListener('click', () => {
        const rows = invalidRows.map((row) => {
            const cells = Array.from(row.children).map((cell) => `"${cell.textContent.replace(/"/g, '""').trim()}"`);
            return cells.join(',');
        });

        const header = '"Satır","Durum","Orijinal veri","Encode edilen veri","Hata mesajı"';
        downloadText('validation_errors.csv', [header, ...rows].join('\n'), 'text/csv;charset=utf-8');
    });

    downloadZip?.addEventListener('click', async () => {
        if (!window.JSZip || validRows.length === 0) {
            return;
        }

        downloadZip.disabled = true;
        downloadZip.textContent = 'ZIP hazırlanıyor...';

        const zip = new JSZip();
        const canvas = document.createElement('canvas');

        for (const row of validRows) {
            const line = row.dataset.line || '0';
            const text = row.dataset.text || '';
            try {
                bwipjs.toCanvas(canvas, optionsFor(text));
                const dataUrl = canvas.toDataURL('image/png');
                zip.file(`barcode_${safeName(line)}.png`, dataUrl.split(',')[1], { base64: true });
            } catch (error) {
                zip.file(`barcode_${safeName(line)}_ERROR.txt`, String(error));
            }
            await new Promise((resolve) => setTimeout(resolve, 0));
        }

        const blob = await zip.generateAsync({ type: 'blob' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'barcodes.zip';
        link.click();
        URL.revokeObjectURL(url);

        downloadZip.disabled = false;
        downloadZip.textContent = 'ZIP indir';
    });

    downloadPdf?.addEventListener('click', async () => {
        const jsPdfApi = window.jspdf?.jsPDF;
        if (!jsPdfApi || validRows.length === 0) {
            return;
        }

        downloadPdf.disabled = true;
        downloadPdf.textContent = 'PDF hazırlanıyor...';

        const pdfSize = Number(document.body.dataset.pdfSize || 100);
        const pdfMargin = Number(document.body.dataset.pdfMargin || 4);
        const doc = new jsPdfApi({
            orientation: 'portrait',
            unit: 'mm',
            format: [pdfSize, pdfSize],
        });

        const canvas = document.createElement('canvas');
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        const margin = pdfMargin;
        const columns = 1;
        const rows = Math.max(1, Math.min(4, Number(pdfPerPage?.value || 1)));
        const gap = 4;
        const cellWidth = (pageWidth - margin * 2 - gap * (columns - 1)) / columns;
        const cellHeight = (pageHeight - margin * 2 - gap * (rows - 1)) / rows;
        const footerHeight = 7;
        const barcodeSize = Math.min(cellWidth, cellHeight - footerHeight);
        const totalPages = Math.ceil(validRows.length / rows);
        let index = 0;

        for (const row of validRows) {
            if (index > 0 && index % (columns * rows) === 0) {
                doc.addPage();
            }

            const position = index % (columns * rows);
            const col = position % columns;
            const pageRow = Math.floor(position / columns);
            const x = margin + col * (cellWidth + gap);
            const y = margin + pageRow * (cellHeight + gap);
            const text = row.dataset.text || '';
            const pageNo = Math.floor(index / rows) + 1;

            try {
                bwipjs.toCanvas(canvas, optionsFor(text));
                const img = canvas.toDataURL('image/png');
                const imgX = x + (cellWidth - barcodeSize) / 2;
                const imgY = y + Math.max(0, (cellHeight - footerHeight - barcodeSize) / 2);
                doc.addImage(img, 'PNG', imgX, imgY, barcodeSize, barcodeSize);
                doc.setFontSize(7);
                doc.text(`${pageNo}/${totalPages}`, x + cellWidth / 2, y + cellHeight - 2, { align: 'center' });
            } catch (error) {
                doc.setFontSize(7);
                doc.text(`${pageNo}/${totalPages}: ${String(error).slice(0, 46)}`, x + 2, y + 8, { maxWidth: cellWidth - 4 });
            }

            index++;
            await new Promise((resolve) => setTimeout(resolve, 0));
        }

        doc.save('barcodes.pdf');
        downloadPdf.disabled = false;
        downloadPdf.textContent = 'PDF indir';
    });
})();
