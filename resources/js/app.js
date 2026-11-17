import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

const round2 = (value) => Math.round((Number(value) + Number.EPSILON) * 100) / 100;

const parseDate = (value) => {
    if (!value) {
        return null;
    }

    const parts = String(value).split('-').map(Number);

    if (parts.length !== 3 || parts.some(Number.isNaN)) {
        return null;
    }

    return new Date(parts[0], parts[1] - 1, parts[2]);
};

const addFrequency = (date, frequency) => {
    const next = new Date(date.getTime());

    if (frequency === 'semanal') {
        next.setDate(next.getDate() + 7);
    } else if (frequency === 'bisemanal') {
        next.setDate(next.getDate() + 14);
    } else if (frequency === 'quincenal') {
        next.setDate(next.getDate() + 15);
    } else if (frequency === 'mensual') {
        const day = next.getDate();
        next.setDate(1);
        next.setMonth(next.getMonth() + 1);
        const lastDay = new Date(next.getFullYear(), next.getMonth() + 1, 0).getDate();
        next.setDate(Math.min(day, lastDay));
    }

    return next;
};

const addMonthsAnchored = (date, months) => {
    const next = new Date(date.getFullYear(), date.getMonth() + months, 1);
    const lastDay = new Date(next.getFullYear(), next.getMonth() + 1, 0).getDate();
    next.setDate(Math.min(date.getDate(), lastDay));

    return next;
};

const formatDate = (date) => {
    if (!date) {
        return '—';
    }

    return new Intl.DateTimeFormat('es-SV', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(date);
};

const currency = (value) =>
    new Intl.NumberFormat('es-SV', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
    }).format(Number(value) || 0);

document.addEventListener('alpine:init', () => {
    Alpine.data('receivableForm', (config = {}) => ({
        monto: config.monto ?? '',
        tasa: config.tasa ?? 8,
        tasaMora: config.tasaMora ?? 5,
        plazo: config.plazo ?? 30,
        frecuencia: config.frecuencia ?? 'semanal',
        fechaDesembolso: config.fechaDesembolso ?? '',
        fechaPrimerPago: config.fechaPrimerPago ?? '',
        clienteId: config.clienteId ?? '',
        errors: config.errors ?? {},

        init() {
            this.$watch('monto', () => this.syncPreview());
            this.$watch('tasa', () => this.syncPreview());
            this.$watch('plazo', () => this.syncPreview());
            this.$watch('frecuencia', (value) => {
                if (value === 'unico') {
                    this.plazo = 1;
                }
                this.updateFirstPayment();
                this.syncPreview();
            });
            this.$watch('fechaDesembolso', () => {
                this.updateFirstPayment();
                this.syncPreview();
            });
            this.$watch('fechaPrimerPago', () => this.syncPreview());

            this.syncPreview();
        },

        get isSingle() {
            return this.frecuencia === 'unico';
        },

        get schedule() {
            const monto = Number(this.monto) || 0;
            const tasa = Number(this.tasa) || 0;
            const plazo = this.isSingle ? 1 : Math.max(1, parseInt(this.plazo, 10) || 1);

            if (monto <= 0 || plazo < 1) {
                return [];
            }

            const dia = tasa / 100 / 365;
            const pago = dia > 0
                ? (monto * dia) / (1 - Math.pow(1 + dia, -plazo))
                : monto / plazo;

            let saldo = round2(monto);
            let fecha = parseDate(this.fechaPrimerPago);
            const rows = [];

            if (!fecha) {
                fecha = parseDate(this.fechaDesembolso);

                if (fecha && !this.isSingle) {
                    fecha = addFrequency(fecha, this.frecuencia);
                }
            }

            const fechaBase = fecha ? new Date(fecha.getTime()) : null;

            for (let numero = 1; numero <= plazo; numero++) {
                if (numero > 1 && fecha) {
                    fecha = this.frecuencia === 'mensual' && fechaBase
                        ? addMonthsAnchored(fechaBase, numero - 1)
                        : addFrequency(fecha, this.frecuencia);
                }

                const interes = round2(saldo * dia);
                let capital = numero === plazo ? saldo : round2(pago - interes);

                if (capital > saldo) {
                    capital = saldo;
                }

                if (capital < 0) {
                    capital = 0;
                }

                const cuota = round2(capital + interes);
                saldo = round2(saldo - capital);

                rows.push({
                    numero,
                    fecha: formatDate(fecha),
                    cuota,
                    interes,
                    capital: round2(capital),
                    saldo: Math.max(saldo, 0),
                });
            }

            return rows;
        },

        get summary() {
            const rows = this.schedule;

            if (!rows.length) {
                return { cuota: 0, interes: 0, total: 0, plazo: 0 };
            }

            const total = round2(rows.reduce((sum, row) => sum + row.cuota, 0));
            const interes = round2(rows.reduce((sum, row) => sum + row.interes, 0));

            return {
                cuota: rows[0].cuota,
                interes,
                total,
                plazo: rows.length,
            };
        },

        updateFirstPayment() {
            const desembolso = parseDate(this.fechaDesembolso);

            if (!desembolso) {
                return;
            }

            const next = this.isSingle ? desembolso : addFrequency(desembolso, this.frecuencia);
            const month = String(next.getMonth() + 1).padStart(2, '0');
            const day = String(next.getDate()).padStart(2, '0');

            this.fechaPrimerPago = `${next.getFullYear()}-${month}-${day}`;
        },

        syncPreview() {
            // Los valores derivados se calculan mediante getters reactivos.
        },

        money(value) {
            return currency(value);
        },

        number(value) {
            return new Intl.NumberFormat('es-SV', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number(value) || 0);
        },

        formatDate(value) {
            return formatDate(parseDate(value));
        },
    }));

    Alpine.data('paymentModal', (config = {}) => ({
        open: false,
        monto: '',
        montoMora: '',
        fecha: config.today ?? '',
        metodo: config.defaultMethod ?? 'efectivo',
        referencia: '',
        notas: '',
        installmentId: '',
        installmentLabel: '',
        materiales: [],
        catalogo: config.materiales ?? [],
        errors: config.errors ?? {},

        init() {
            if (this.materiales.length === 0) {
                this.addMaterial();
            }
        },

        get materialesValidos() {
            return this.materiales.filter((item) =>
                item.material_id !== ''
                && Number(item.cantidad) > 0
                && Number(item.precio_unitario) >= 0
            );
        },

        get materialTotal() {
            return round2(this.materialesValidos.reduce(
                (sum, item) => sum + Number(item.cantidad) * Number(item.precio_unitario),
                0,
            ));
        },

        // Total entregado (dinero + materiales). La mora se descuenta de este total.
        get total() {
            return round2((Number(this.monto) || 0) + this.materialTotal);
        },

        get totalCuotas() {
            return Math.max(round2(this.total - (Number(this.montoMora) || 0)), 0);
        },

        precioMaterial(materialId) {
            const material = this.catalogo.find((item) => String(item.id) === String(materialId));

            return material ? Number(material.precio) : '';
        },

        addMaterial(materialId = '', precioUnitario = '') {
            this.materiales.push({
                material_id: materialId,
                cantidad: 1,
                precio_unitario: precioUnitario !== '' ? precioUnitario : this.precioMaterial(materialId),
            });
        },

        removeMaterial(index) {
            this.materiales.splice(index, 1);

            if (this.materiales.length === 0) {
                this.addMaterial();
            }
        },

        seleccionarMaterial(index) {
            this.materiales[index].precio_unitario = this.precioMaterial(this.materiales[index].material_id);
        },

        show(preset = null) {
            this.monto = preset?.monto ?? '';
            this.montoMora = preset?.mora ?? '';
            this.installmentId = preset?.id ?? '';
            this.installmentLabel = preset?.label ?? '';
            this.fecha = config.today ?? '';
            this.metodo = config.defaultMethod ?? 'efectivo';
            this.referencia = '';
            this.notas = '';
            this.materiales = [];

            if (Array.isArray(preset?.materiales)) {
                preset.materiales.forEach((item) => this.addMaterial(
                    item.material_id ?? '',
                    item.precio_unitario ?? '',
                ));
            } else {
                this.addMaterial();
            }

            this.open = true;

            this.$nextTick(() => this.$refs.montoInput?.focus());
        },

        close() {
            this.open = false;
        },

        money(value) {
            return currency(value);
        },
    }));

    Alpine.data('payablePaymentModal', (config = {}) => ({
        open: false,
        monto: '',
        fecha: config.today ?? '',
        metodo: config.defaultMethod ?? 'efectivo',
        referencia: '',
        notas: '',

        show(preset = null) {
            this.monto = preset?.monto ?? '';
            this.fecha = config.today ?? '';
            this.metodo = config.defaultMethod ?? 'efectivo';
            this.referencia = '';
            this.notas = '';
            this.open = true;

            this.$nextTick(() => this.$refs.montoInput?.focus());
        },

        close() {
            this.open = false;
        },

        money(value) {
            return currency(value);
        },
    }));

    Alpine.data('confirmAction', () => ({
        open: false,
        message: '¿Confirmas esta acción?',
        onConfirm: null,

        ask(message, callback) {
            this.message = message;
            this.onConfirm = callback;
            this.open = true;
        },

        confirm() {
            const callback = this.onConfirm;
            this.onConfirm = null;
            this.open = false;
            callback?.();
        },
    }));
});

Alpine.start();
