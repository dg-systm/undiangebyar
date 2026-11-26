<template>
    <span class="plate">
        <span class="no">
            <template v-for="(slotChar, i) in pattern" :key="i">
                <i v-if="slotChar === ' '" class="sp"></i>
                <b v-else class="ch" :class="{ land: landed[i] }">
                    <span
                        class="reel"
                        :class="{ rolling: rolling[i], settle: settle[i] }"
                        :style="{
                            transform: `translateY(calc(${offsets[i] || 0} * -1.35em))`,
                        }"
                    >
                        <i v-for="(rc, j) in strips[i]" :key="j">{{ rc }}</i>
                    </span>
                </b>
            </template>
        </span>
        <!-- Baris masa berlaku (exp): SELALU statis, tidak ikut animasi. -->
        <i v-if="exp" class="exp">{{ exp }}</i>
    </span>
</template>

<script>
import { reactive, computed, watch, onMounted, onUnmounted } from "vue";

/**
 * Piringan nomor polisi ala mesin slot (meniru halaman referensi
 * "Tabungan 2 Jt"). Setiap karakter nomor polisi berputar sendiri-sendiri
 * dan berhenti (settle) satu per satu dari kiri ke kanan.
 *
 * - Saat `spinning`, semua karakter menggulung strip acak (CSS animation).
 * - Saat `spinning` menjadi false, tiap karakter menerima delay
 *   `settleDelay + index*45` ms untuk mendarat ke karakter tujuannya.
 * - Karakter yang tidak ada tujuannya (slot kosong pola "XX 0000 XXX")
 *   dibiarkan kosong; lebar piringan tetap pola penuh.
 * - Komponen meng-emit `landed` setelah seluruh karakter selesai mendarat,
 *   dipakai halaman untuk menampilkan nama pemenang.
 */
const PATTERN = [..."XX 0000 XXX"];
const poolFor = (kind) =>
    kind === "d" ? "0123456789" : "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
const rnd = (chars) => chars[Math.floor(Math.random() * chars.length)];

export default {
    name: "PlateReel",
    props: {
        prefix: { type: String, default: "" },
        digits: { type: [String, Number], default: "" },
        suffix: { type: String, default: "" },
        // Masa berlaku (exp) di bawah nomor polisi, contoh "08 · 27".
        // Statis: tidak pernah dianimasikan. Kosong = baris tidak tampil
        // (halaman undian per koordinator tidak memakai exp).
        exp: { type: String, default: "" },
        spinning: { type: Boolean, default: false },
        settleDelay: { type: Number, default: 0 },
    },
    emits: ["landed"],
    setup(props, { emit }) {
        const pattern = PATTERN;
        const kinds = PATTERN.map((c) => (/\d/.test(c) ? "d" : "l"));
        const charSlots = [];
        PATTERN.forEach((c, i) => {
            if (c !== " ") charSlots.push(i);
        });

        // Karakter tujuan per slot, mengikuti parsing halaman referensi:
        // prefix dipad ke 2 huruf, angka apa adanya, suffix dipad ke 3 huruf.
        const targets = computed(() => {
            const p = String(props.prefix || "").toUpperCase();
            const d = String(props.digits || "");
            const s = String(props.suffix || "").toUpperCase();
            const seq = [
                ...p.padEnd(2, " "),
                ...d,
                ...s.padEnd(3, " "),
            ].map((x) => x.trim());

            const map = {};
            charSlots.forEach((slot, k) => {
                map[slot] = seq[k] || "";
            });
            return map;
        });

        const state = reactive({
            strips: {},
            rolling: {},
            settle: {},
            landed: {},
            offsets: {},
        });

        let timers = [];
        const clearTimers = () => {
            timers.splice(0).forEach(clearTimeout);
        };

        const showStatic = () => {
            charSlots.forEach((slot) => {
                state.strips[slot] = [targets.value[slot] || " "];
                state.rolling[slot] = false;
                state.settle[slot] = false;
                state.landed[slot] = false;
                state.offsets[slot] = 0;
            });
        };

        const rollSlot = (slot) => {
            const chars = poolFor(kinds[slot]);
            const strip = [...Array(10)].map(() => rnd(chars));

            state.settle[slot] = false;
            state.landed[slot] = false;
            state.strips[slot] = strip.concat(strip[0]);
            state.offsets[slot] = 0;
            state.rolling[slot] = true;
        };

        const startRolling = () => {
            clearTimers();
            charSlots.forEach((slot) => rollSlot(slot));
        };

        const landSlot = (slot) => {
            const target = targets.value[slot] || "";
            const chars = poolFor(kinds[slot]);

            state.rolling[slot] = false;
            state.settle[slot] = true;
            state.landed[slot] = true;
            state.strips[slot] = [...Array(5)]
                .map(() => rnd(chars))
                .concat(target || " ");
            state.offsets[slot] = 0;

            // Dua frame agar posisi awal sempat ter-render, lalu transisi
            // bergerak ke karakter tujuan (mirip void offsetWidth pada CSS).
            requestAnimationFrame(() =>
                requestAnimationFrame(() => {
                    state.offsets[slot] = 5;
                }),
            );

            // Slot tanpa karakter tujuan TIDAK disusutkan: lebar piringan
            // tetap pola penuh "XX 0000 XXX" setelah semua mendarat.
        };

        const scheduleLands = () => {
            clearTimers();
            charSlots.forEach((slot, k) => {
                timers.push(
                    setTimeout(
                        () => landSlot(slot),
                        props.settleDelay + k * 45,
                    ),
                );
            });

            const lastLandIn = (charSlots.length - 1) * 45;
            // Emit setelah transisi settle (0.45s) selesai -> baris dianggap
            // tampil sempurna ketika piringan benar-benar berhenti bergerak.
            timers.push(
                setTimeout(() => emit("landed"), props.settleDelay + lastLandIn + 450),
            );
        };

        watch(
            () => props.spinning,
            (spinning) => {
                if (spinning) {
                    startRolling();
                } else {
                    scheduleLands();
                }
            },
        );

        onMounted(() => {
            if (props.spinning) {
                startRolling();
            } else {
                showStatic();
            }
        });

        onUnmounted(clearTimers);

        return {
            pattern,
            strips: state.strips,
            rolling: state.rolling,
            settle: state.settle,
            landed: state.landed,
            offsets: state.offsets,
        };
    },
};
</script>

<style scoped>
.plate {
    --acc: #2563eb;
    background: linear-gradient(
        180deg,
        var(--kec-td-bg, #fff),
        color-mix(in srgb, var(--kec-td-bg, #fff) 88%, #12151c)
    );
    color: #12151c;
    border: 2px solid #12151c;
    border-radius: 0.4em;
    box-shadow:
        inset 0 0 0 1px #fff,
        0 0.12em 0.35em -0.1em rgba(14, 36, 51, 0.4);
    padding: 0.05em 0.2em;
    display: flex;
    flex-direction: column;
    align-items: center;
    white-space: nowrap;
    font-family: "Barlow Condensed", "Arial Narrow", system-ui, sans-serif;
    font-size: 1.3em;
    font-weight: 700;
    line-height: 1.35;
}

/* Baris nomor polisi (dibungkus agar exp bisa ditambah di bawahnya). */
.no {
    display: flex;
    justify-content: center;
}

/* Baris masa berlaku: statis, di bawah nomor, dipisah garis. */
.exp {
    align-self: stretch;
    margin-top: 0.075em;
    padding-top: 0.075em;
    border-top: 0.05em solid #1a1a1a;
    font-size: 0.325em;
    font-weight: 600;
    font-style: normal;
    line-height: 1.25;
    letter-spacing: 0.2em;
    text-align: center;
    white-space: nowrap;
}

.ch {
    display: block;
    width: 0.47em;
    height: 1.35em;
    clip-path: inset(0 -0.3em);
}

.reel {
    display: block;
    will-change: transform;
}

.reel i {
    display: block;
    height: 1.35em;
    line-height: 1.35em;
    text-align: center;
    font-style: normal;
}

.reel.rolling {
    animation: plate-roll 0.5s linear infinite;
    filter: blur(0.6px);
}

.reel.settle {
    transition: transform 0.45s cubic-bezier(0.1, 0.7, 0.2, 1);
}

.sp {
    display: block;
    width: 0.3em;
}

@keyframes plate-roll {
    to {
        transform: translateY(calc(-10 * 1.35em));
    }
}

.ch.land {
    animation: plate-flash 0.9s ease-out;
}

@keyframes plate-flash {
    from {
        color: var(--acc);
    }
}

@media (prefers-reduced-motion: reduce) {
    .ch,
    .reel.settle {
        transition: none;
    }
    .reel.rolling {
        animation: none;
    }
    .ch.land {
        animation: none;
    }
}
</style>
