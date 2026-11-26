<template>
    <span class="slot-reel" :class="{ 'is-rolling': rolling }">
        <span v-if="rolling" class="slot-reel-strip" :style="{ animationDuration: duration + 's' }">
            <span v-for="(char, index) in strip" :key="index" class="slot-reel-char">{{ char }}</span>
        </span>
        <span v-else class="slot-reel-char is-final">{{ value }}</span>
    </span>
</template>

<script>
import { computed } from "vue";

/**
 * Reel vertikal ala mesin slot untuk satu karakter.
 *
 * - Saat `rolling`, strip karakter digulung terus menerus (CSS animation).
 * - Saat berhenti, karakter final (`value`) ditampilkan.
 */
export default {
    name: "SlotReel",
    props: {
        value: { type: String, required: true },
        charset: { type: String, required: true },
        rolling: { type: Boolean, default: false },
        duration: { type: Number, default: 2.2 },
    },
    setup(props) {
        // Strip memuat charset dua kali agar animasi -50% menghasilkan
        // penggulungan mulus tanpa lompatan saat loop.
        const strip = computed(() => (props.charset + props.charset).split(""));

        return { strip };
    },
};
</script>

<style scoped>
.slot-reel {
    display: inline-block;
    width: 1ch;
    height: 1.25em;
    overflow: hidden;
    vertical-align: bottom;
    text-align: center;
}

.slot-reel-char {
    display: block;
    height: 1.25em;
    line-height: 1.25;
}

.slot-reel-strip {
    display: block;
    will-change: transform;
    animation: slot-reel-roll 2.2s linear infinite;
}

.slot-reel-char.is-final {
    animation: slot-reel-settle 0.25s ease;
}

@keyframes slot-reel-roll {
    from {
        transform: translateY(0);
    }

    to {
        transform: translateY(-50%);
    }
}

@keyframes slot-reel-settle {
    from {
        opacity: 0.35;
    }

    to {
        opacity: 1;
    }
}
</style>
