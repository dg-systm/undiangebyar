<template>
    <div
        id="page-container"
        class="sidebar-inverse enable-page-overlay page-header-fixed side-trans-enabled"
        style="min-height: 100vh"
    >
        <aside
            id="side-overlay"
            data-simplebar="init"
            class="simplebar-scrollable-y"
        >
            <div class="simplebar-wrapper" style="margin: 0px">
                <div class="simplebar-height-auto-observer-wrapper">
                    <div class="simplebar-height-auto-observer"></div>
                </div>
                <div class="simplebar-mask">
                    <div
                        class="simplebar-offset"
                        style="right: 0px; bottom: 0px"
                    >
                        <div
                            class="simplebar-content-wrapper"
                            tabindex="0"
                            role="region"
                            aria-label="scrollable content"
                            style="height: 100%"
                        >
                            <div style="padding: 0px">
                                <div class="content-header">
                                    <div class="settings-panel-title">
                                        <i class="fa fa-fw fa-gear"></i>
                                        <span>Pengaturan Grand Prize</span>
                                    </div>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-alt-danger settings-panel-close"
                                        data-toggle="layout"
                                        data-action="side_overlay_close"
                                        title="Tutup"
                                        aria-label="Tutup pengaturan grand prize"
                                    >
                                        <i class="fa fa-fw fa-times"></i>
                                    </button>
                                </div>
                                <div class="content-side">
                                    <div class="block">
                                        <div
                                            class="block-content block-content-full"
                                        >
                                            <!-- PRIZE CATEGORY -->
                                            <div class="mb-3">
                                                <label
                                                    class="form-label fw-semibold"
                                                >
                                                    Kategori Hadiah
                                                </label>
                                                <select
                                                    v-model="selectedCategoryId"
                                                    @change="onCategoryChange"
                                                    class="form-select form-select-sm"
                                                >
                                                    <option value="">
                                                        -- Pilih Kategori --
                                                    </option>
                                                    <option
                                                        v-for="category in prizeCategories"
                                                        :key="category.id"
                                                        :value="category.id"
                                                    >
                                                        {{ category.name }}
                                                    </option>
                                                </select>
                                            </div>

                                            <!-- PRIZE MULTI-SELECT -->
                                            <div class="mb-3">
                                                <label
                                                    class="form-label fw-semibold"
                                                >
                                                    Hadiah Grand Prize
                                                </label>
                                                <div
                                                    v-if="!selectedCategoryId"
                                                    class="text-muted small"
                                                >
                                                    Pilih kategori untuk melihat
                                                    hadiah.
                                                </div>
                                                <div
                                                    v-else-if="
                                                        filteredPrizes.length ===
                                                        0
                                                    "
                                                    class="text-muted small"
                                                >
                                                    Tidak ada hadiah pada
                                                    kategori ini.
                                                </div>
                                                <div
                                                    v-else
                                                    class="border rounded p-2"
                                                    style="
                                                        max-height: 240px;
                                                        overflow-y: auto;
                                                    "
                                                >
                                                    <div
                                                        v-for="item in filteredPrizes"
                                                        :key="item.id"
                                                        class="form-check mb-1"
                                                    >
                                                        <input
                                                            class="form-check-input"
                                                            type="checkbox"
                                                            :id="`prize-${item.id}`"
                                                            :value="item.id"
                                                            v-model="
                                                                selectedPrizeIds
                                                            "
                                                        />
                                                        <label
                                                            class="form-check-label fs-xs"
                                                            :for="`prize-${item.id}`"
                                                        >
                                                            {{ item.name }}
                                                            <span
                                                                class="text-muted"
                                                                >(stok:
                                                                {{
                                                                    item.available_quantity
                                                                }})</span
                                                            >
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- QUANTITY -->
                                            <div class="mb-3">
                                                <label
                                                    class="form-label fw-semibold"
                                                >
                                                    <i
                                                        class="fa fa-users me-1 text-primary"
                                                    ></i>
                                                    Jumlah Pemenang
                                                </label>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    :max="maxQuantity"
                                                    v-model.number="quantity"
                                                    class="form-control"
                                                    placeholder="Masukan Jumlah.."
                                                />
                                                <div
                                                    class="text-muted mt-1"
                                                    style="font-size: 0.75rem"
                                                >
                                                    Maks:
                                                    <strong>{{
                                                        maxQuantity
                                                    }}</strong>
                                                    (stok tersedia)
                                                </div>
                                            </div>

                                            <!-- SCOPE INFO -->
                                            <div
                                                class="mb-3 p-2 rounded"
                                                style="
                                                    background: rgba(
                                                        255,
                                                        215,
                                                        0,
                                                        0.08
                                                    );
                                                    border: 1px solid
                                                        rgba(255, 215, 0, 0.3);
                                                "
                                            >
                                                <div
                                                    class="d-flex align-items-center gap-2"
                                                >
                                                    <i
                                                        class="fa fa-globe text-warning"
                                                    ></i>
                                                    <div
                                                        class="fw-semibold fs-sm text-warning"
                                                    >
                                                        Mode Global &mdash;
                                                        Semua Kecamatan
                                                    </div>
                                                </div>
                                                <div
                                                    class="text-muted mt-1"
                                                    style="font-size: 0.72rem"
                                                >
                                                    Jumlah pemenang fleksibel
                                                    sesuai kebutuhan acara.
                                                </div>
                                            </div>

                                            <!-- TOMBOL SIMPAN & SIGN-IN -->
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <button
                                                        class="btn btn-alt-warning w-100"
                                                        v-on:click="saveSetting"
                                                        :disabled="
                                                            !!settingError
                                                        "
                                                    >
                                                        <i
                                                            class="fa fa-sync opacity-50 me-1"
                                                        ></i>
                                                        Simpan
                                                    </button>
                                                </div>
                                                <div class="col-6">
                                                    <a
                                                        href="/admin"
                                                        class="btn btn-alt-success w-100"
                                                        ><i
                                                            class="fa fa-sign-in opacity-50 me-1"
                                                        ></i>
                                                        Sign-in</a
                                                    >
                                                </div>
                                            </div>

                                            <!-- SUMMARY -->
                                            <div
                                                class="mb-3 p-2 rounded"
                                                style="
                                                    background: rgba(
                                                        0,
                                                        0,
                                                        0,
                                                        0.05
                                                    );
                                                "
                                            >
                                                <div
                                                    class="d-flex justify-content-between small mb-1"
                                                >
                                                    <span>Hadiah:</span>
                                                    <strong
                                                        class="text-warning"
                                                        >{{
                                                            selectedPrizeLabel ||
                                                            "-"
                                                        }}</strong
                                                    >
                                                </div>
                                                <div
                                                    class="d-flex justify-content-between small mb-1"
                                                >
                                                    <span
                                                        >Jumlah pemenang:</span
                                                    >
                                                    <strong
                                                        :class="
                                                            quantity > 0
                                                                ? 'text-primary'
                                                                : 'text-muted'
                                                        "
                                                        >{{
                                                            quantity
                                                        }}
                                                        orang</strong
                                                    >
                                                </div>
                                                <div
                                                    v-if="settingError"
                                                    class="mt-1 text-danger small fw-semibold"
                                                >
                                                    <i
                                                        class="fa fa-exclamation-triangle me-1"
                                                    ></i
                                                    >{{ settingError }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- PANEL PENGATURAN TAMPILAN: dibuka lewat tombol setting di samping pembuka sidebar.
             Pengaturan tampilan hanya ada di panel ini. -->
        <aside
            id="side-overlay-settings"
            ref="settingsPanel"
            aria-label="Pengaturan Tampilan"
            :class="{ 'is-open': showDisplaySettings }"
        >
            <div class="content-header">
                <div class="settings-panel-title">
                    <i class="fa fa-fw fa-sliders-h"></i>
                    <span>Pengaturan Tampilan</span>
                </div>
                <button
                    type="button"
                    class="btn btn-sm btn-alt-danger settings-panel-close"
                    title="Tutup"
                    aria-label="Tutup pengaturan tampilan"
                    @click="showDisplaySettings = false"
                >
                    <i class="fa fa-fw fa-times"></i>
                </button>
            </div>
            <div class="content-side">
                <div class="block">
                    <div class="block-content block-content-full">
                        <!-- Konten pengaturan tampilan: langsung tampil tanpa tombol Set -->
                        <div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">
                                    <i
                                        class="fa fa-expand-arrows-alt me-1 text-warning"
                                    ></i>
                                    Ukuran Tombol Start
                                </label>
                                <div class="d-flex align-items-center gap-2">
                                    <input
                                        type="range"
                                        class="form-range flex-grow-1"
                                        min="80"
                                        max="220"
                                        step="5"
                                        v-model.number="drawButtonSize"
                                    />
                                    <input
                                        type="number"
                                        class="form-control form-control-sm"
                                        style="width: 78px"
                                        min="80"
                                        max="220"
                                        step="5"
                                        v-model.number="drawButtonSize"
                                    />
                                </div>
                                <div
                                    class="text-muted mt-1"
                                    style="font-size: 0.72rem"
                                >
                                    {{ drawButtonSize }}%
                                </div>
                            </div>

                            <div class="mb-3">
                                <div
                                    class="d-flex justify-content-between align-items-center"
                                >
                                    <label class="form-label fw-semibold mb-0">
                                        <i
                                            class="fa fa-arrows-up-down me-1 text-warning"
                                        ></i>
                                        Jarak Undian dari Atas
                                    </label>
                                    <button
                                        type="button"
                                        class="btn btn-xs btn-alt-secondary"
                                        @click="resetUndianTopOffset"
                                    >
                                        Reset
                                    </button>
                                </div>
                                <div
                                    class="d-flex align-items-center gap-2 mt-1"
                                >
                                    <input
                                        type="range"
                                        class="form-range flex-grow-1"
                                        min="0"
                                        max="500"
                                        step="5"
                                        v-model.number="undianTopOffset"
                                    />
                                    <input
                                        type="number"
                                        class="form-control form-control-sm"
                                        style="width: 84px"
                                        min="0"
                                        max="500"
                                        step="5"
                                        v-model.number="undianTopOffset"
                                    />
                                </div>
                                <div
                                    class="text-muted mt-1"
                                    style="font-size: 0.72rem"
                                >
                                    {{ undianTopOffset }} px
                                </div>
                            </div>

                            <div class="mb-3">
                                <div
                                    class="d-flex justify-content-between align-items-center"
                                >
                                    <label class="form-label fw-semibold mb-0">
                                        <i
                                            class="fa fa-font me-1 text-warning"
                                        ></i>
                                        Ukuran Font Tabel
                                    </label>
                                    <button
                                        type="button"
                                        class="btn btn-xs btn-alt-secondary"
                                        @click="resetTableFontSize"
                                    >
                                        Reset
                                    </button>
                                </div>
                                <div
                                    class="d-flex align-items-center gap-2 mt-1"
                                >
                                    <input
                                        type="range"
                                        class="form-range flex-grow-1"
                                        min="8"
                                        max="28"
                                        step="1"
                                        v-model.number="tableFontSize"
                                    />
                                    <input
                                        type="number"
                                        class="form-control form-control-sm"
                                        style="width: 84px"
                                        min="8"
                                        max="28"
                                        step="1"
                                        v-model.number="tableFontSize"
                                    />
                                </div>
                                <div
                                    class="text-muted mt-1"
                                    style="font-size: 0.72rem"
                                >
                                    {{ tableFontSize }} px
                                </div>
                            </div>

                            <!-- SOUND EFFECT: on/off + test -->
                            <div class="mb-3">
                                <div
                                    class="d-flex justify-content-between align-items-center"
                                >
                                    <label class="form-label fw-semibold mb-0">
                                        <i
                                            class="fa fa-volume-up me-1 text-warning"
                                        ></i>
                                        Sound Effect
                                    </label>
                                    <button
                                        type="button"
                                        class="btn btn-xs btn-alt-secondary"
                                        @click="testSound"
                                    >
                                        <i class="fa fa-play me-1"></i>Test
                                        Suara
                                    </button>
                                </div>
                                <div
                                    class="d-flex align-items-center gap-2 mt-2"
                                >
                                    <button
                                        type="button"
                                        class="btn btn-xs"
                                        :class="
                                            soundOn
                                                ? 'btn-alt-success'
                                                : 'btn-alt-danger'
                                        "
                                        @click="toggleSound"
                                    >
                                        {{
                                            soundOn
                                                ? "Suara: Aktif"
                                                : "Suara: Mati"
                                        }}
                                    </button>
                                    <span
                                        class="text-muted"
                                        style="font-size: 0.72rem"
                                    >
                                        {{
                                            soundOn
                                                ? "Efek suara menyala saat mengundi."
                                                : "Efek suara dimatikan."
                                        }}
                                    </span>
                                </div>
                            </div>

                            <!-- BACKGROUND POSTER & HALAMAN GRAND PRIZE -->
                            <BackgroundSetting
                                storage-key="grandPrizeBackgroundImage"
                                poster-storage-key="grandPrizeBackgroundPosterImage"
                                variant="grand-prize"
                                accent="warning"
                                :fallback-poster="bgImageInitial"
                                :fallback-page="bgImage"
                                @change="onBackgroundChange"
                                @size-change="onBackgroundSizeChange"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <header
            id="page-header"
            style="background-color: rgba(0, 0, 0, 0) !important"
        >
            <div class="content-header">
                <div class="space-x-1"></div>
                <div class="space-x-1">
                    <button
                        type="button"
                        class="btn btn-sm btn-alt-secondary me-1 page-nav-btn"
                        title="Kembali ke Undian Biasa"
                        :disabled="pageSwitching"
                        @click="goToRegularDraw"
                    >
                        <span
                            v-if="pageSwitching"
                            class="page-nav-spinner"
                            aria-hidden="true"
                        ></span>
                        <i v-else class="fa fa-fw fa-arrow-left"></i>
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm btn-alt-secondary"
                        data-toggle="layout"
                        data-action="side_overlay_toggle"
                        @click="showDisplaySettings = false"
                    >
                        <i class="fa fa-fw fa-stream"></i>
                    </button>
                    <button
                        type="button"
                        class="btn btn-sm btn-alt-warning ms-1 page-nav-btn"
                        title="Pengaturan Tampilan"
                        data-settings-toggle
                        @click="toggleDisplaySettings"
                    >
                        <i class="fa fa-fw fa-sliders-h"></i>
                    </button>
                </div>
            </div>
        </header>
        <main id="main-container" style="padding-top: unset !important">
            <div class="bg-undian" @click="trigger" :style="backgroundStyle">
                <div class="bg-black-50">
                    <div class="content container-fluid undian-content">
                        <div
                            class="undian-center"
                            :style="{ marginTop: undianTopOffset + 'px' }"
                        >
                            <template v-if="hasSetup">
                                <section
                                    class="gp"
                                    :style="tableAppearanceStyle"
                                >
                                    <ol class="gp-list">
                                        <li
                                            v-for="item in undianList"
                                            :key="item.id"
                                            class="gp-row"
                                            :class="{
                                                spin: drawing && !item.revealed,
                                                lock: item.revealed,
                                            }"
                                        >
                                            <span class="gp-no">{{
                                                item.id
                                            }}</span>
                                            <div class="plate-cell">
                                                <PlateReel
                                                    :prefix="item.kodeWilayah"
                                                    :digits="item.angka"
                                                    :suffix="item.kodeSeri"
                                                    :exp="
                                                        item.revealed
                                                            ? item.exp || ''
                                                            : 'XX · XX'
                                                    "
                                                    :spinning="
                                                        drawing &&
                                                        !item.revealed
                                                    "
                                                    :settle-delay="0"
                                                    @landed="onPlateLanded"
                                                />
                                            </div>
                                            <article class="ktp">
                                                <div class="ktp-top">
                                                    PROVINSI JAWA TENGAH
                                                </div>
                                                <dl class="ktp-fields">
                                                    <div>
                                                        <dt>Nama</dt>
                                                        <dd>
                                                            {{
                                                                item.revealed
                                                                    ? item.nama ||
                                                                      "-"
                                                                    : item.dispNama
                                                            }}
                                                        </dd>
                                                    </div>
                                                    <div>
                                                        <dt>Alamat</dt>
                                                        <dd>
                                                            {{
                                                                item.revealed
                                                                    ? maskAlamat(
                                                                          item.alamat,
                                                                      )
                                                                    : item.dispAlamat
                                                            }}
                                                        </dd>
                                                    </div>
                                                    <div>
                                                        <dt>Kecamatan</dt>
                                                        <dd>
                                                            {{
                                                                item.revealed
                                                                    ? item.lokasi ||
                                                                      "-"
                                                                    : item.dispLokasi
                                                            }}
                                                        </dd>
                                                    </div>
                                                    <div>
                                                        <dt>Roda</dt>
                                                        <dd>
                                                            {{
                                                                item.revealed
                                                                    ? item.roda ||
                                                                      "-"
                                                                    : item.dispRoda
                                                            }}
                                                        </dd>
                                                    </div>
                                                </dl>
                                            </article>
                                            <aside class="prize">
                                                <small>Hadiah</small>
                                                <strong>{{
                                                    item.prize?.description ||
                                                    item.prize?.name ||
                                                    "?"
                                                }}</strong>
                                            </aside>
                                        </li>
                                        <li
                                            v-if="undianList.length === 0"
                                            class="gp-empty"
                                        >
                                            Belum ada pemenang. Atur pengaturan
                                            dan mulai pengundian.
                                        </li>
                                    </ol>
                                </section>

                                <div class="text-center draw-button-wrap">
                                    <button
                                        type="button"
                                        class="btn btn-rounded btn-xl fw-semibold animated shake"
                                        id="btnDraw"
                                        :class="drawButtonClass"
                                        @click="drawHandle"
                                        :style="drawButtonStyle"
                                        :disabled="drawButtonDisabled"
                                        autofocus
                                    >
                                        {{ drawButtonLabel }}
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
.bg-undian {
    background-repeat: no-repeat;
    background-position: center center;
    background-size: cover;
    min-height: 100vh;
    height: 100vh;
}

#page-container {
    overflow: auto;
    min-height: 100vh;
    scrollbar-width: none;
    /* Firefox */
}

#page-container::-webkit-scrollbar {
    display: none;
    /* Chrome, Safari, Edge */
}

.h1-custom {
    font-size: 5.571429rem;
}

.bg-black-50 {
    min-height: 100vh;
    background-color: transparent !important;
}

.bg-black-op-50 {
    background-color: rgba(0, 0, 0, 0.75) !important;
}

/* Konten (tabel + tombol): jarak dari atas diatur lewat undianTopOffset */
.undian-content {
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 140px);
}

.undian-center {
    margin-bottom: auto;
    width: 100%;
}

/* ====== Hasil undian: gaya referensi grand-prize-1-halaman ======
   Baris kartu: nomor urut, plat nomor, kartu KTP, kartu hadiah.
   Semua ukuran memakai em mengikuti --gp-card-fs agar pengaturan
   "Ukuran Font Tabel" tetap berfungsi. */
.gp {
    --acc: #0b4fc2;
    /* Batas lebar dalam em (bukan px) agar proporsi baris & kartu tetap
       identik dan tetap center di tengah pada ukuran font tabel berapa
       pun: 79.4em x 14.86px = ~1180px pada ukuran font bawaan. */
    max-width: 79.4em;
    margin: 0 auto;
    width: 100%;
    font-family:
        "Inter",
        system-ui,
        -apple-system,
        "Segoe UI",
        Roboto,
        sans-serif;
    font-size: var(--gp-card-fs, 13px);
}

.gp-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 0.75em;
}

.gp-row {
    display: grid;
    grid-template-columns: 2.4em 15.6em 1fr 10.6em;
    gap: 1em;
    align-items: stretch;
}

/* nomor urut */
.gp-no {
    align-self: center;
    width: 2.4em;
    height: 2.4em;
    border-radius: 50%;
    background: #fff;
    color: #0b4fc2;
    display: grid;
    place-items: center;
    font-weight: 700;
    box-shadow: 0 0.2em 0.5em rgba(8, 30, 90, 0.25);
}

/* sel plat nomor: menampung PlateReel (animasi slot tetap dipakai) */
.plate-cell {
    display: flex;
    align-items: center;
    justify-content: center;
}

/* kulit plat nomor ala TNKB: putih, border hitam, baut kiri-kanan.
   Menimpa gaya bawaan PlateReel tanpa mengubah halaman undian biasa. */
.plate-cell .plate {
    width: 100%;
    background: #fff;
    border: 0.11em solid #1a1a1a;
    border-radius: 0.3em;
    box-shadow: 0 0.2em 0.45em rgba(8, 30, 90, 0.3);
    padding: 0.3em 0.35em;
    position: relative;
    font-size: 2.2em;
}

.plate-cell .plate::before,
.plate-cell .plate::after {
    content: "";
    position: absolute;
    top: 50%;
    width: 0.15em;
    height: 0.15em;
    border-radius: 50%;
    background: #c9c9c9;
    box-shadow: inset 0 0 0 1.5px #9a9a9a;
    transform: translateY(-50%);
}

.plate-cell .plate::before {
    left: 0.15em;
}

.plate-cell .plate::after {
    right: 0.15em;
}

/* ---------- kartu KTP ---------- */
.ktp {
    background:
        radial-gradient(
            circle at 82% 18%,
            rgba(255, 255, 255, 0.7) 0,
            rgba(255, 255, 255, 0) 48%
        ),
        repeating-linear-gradient(
            115deg,
            rgba(255, 255, 255, 0.22) 0 2px,
            rgba(255, 255, 255, 0) 2px 9px
        ),
        linear-gradient(135deg, #8cc5ea 0%, #c8e5f6 46%, #a4d3ef 100%);
    color: #0e1b30;
    border-radius: 0.75em;
    padding: 0.4em 0.875em 0.55em;
    border: 1px solid #6ba8d0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    box-shadow: 0 0.5em 1.1em rgba(8, 30, 90, 0.25);
    overflow: hidden;
}

.ktp-top {
    text-align: center;
    font-size: 0.75em;
    font-weight: 700;
    letter-spacing: 0.06em;
    margin-bottom: 0.3em;
    color: #0b1626;
}

.ktp-fields {
    margin: 0;
    display: grid;
    /* Nama diberi ruang paling lebar; alamat, kecamatan, dan roda
       dipadatkan ke kanan agar tidak menggeser bentuk kartu. */
    grid-template-columns: 1.5fr 1.4fr 0.8fr 0.45fr;
    gap: 0.35em 0.875em;
}

.ktp-fields div {
    min-width: 0;
}

.ktp-fields dt {
    font-size: 1em;
    font-weight: 500;
    color: #1f4266;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ktp-fields dd {
    margin: 1px 0 0;
    font-size: 0.875em;
    font-weight: 600;
    line-height: 1.25;
    /* Satu baris saja: teks panjang dipotong dengan elipsis agar tinggi
       baris tabel selalu sama (tidak naik turun saat mengacak/reveal). */
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ---------- kartu hadiah ---------- */
.prize {
    background: rgba(255, 255, 255, 0.94);
    color: #0e1b30;
    border-radius: 0.75em;
    border-top: 0.375em solid #f6c945;
    box-shadow: 0 0.5em 1.1em rgba(8, 30, 90, 0.25);
    padding: 0.5em 0.75em;
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 0.15em;
}

.prize small {
    font-size: 0.75em;
    font-weight: 600;
    color: #1f4266;
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.prize strong {
    font-size: 1.2em;
    font-weight: 700;
    line-height: 1.2;
    color: #0b3d94;
    max-width: 100%;
    /* Satu baris dengan elipsis agar tinggi kartu hadiah tetap sama. */
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* baris kosong saat belum ada pemenang */
.gp-empty {
    padding: 1.5em;
    background: rgba(255, 255, 255, 0.92);
    color: #0e1b30;
    border-radius: 0.75em;
    text-align: center;
    font-weight: 600;
}

/* efek: baris yang sedang diacak redup, saat terkunci muncul sorotan */
.gp-row.spin .plate {
    opacity: 0.8;
}

.gp-row.spin .ktp-fields dd {
    opacity: 0.7;
}

.gp-row.lock .plate,
.gp-row.lock .ktp {
    animation: gp-pop 0.4s ease-out;
}

@keyframes gp-pop {
    0% {
        transform: scale(0.97);
    }

    60% {
        transform: scale(1.015);
    }

    100% {
        transform: scale(1);
    }
}

@media (prefers-reduced-motion: reduce) {
    .gp-row.lock .plate,
    .gp-row.lock .ktp {
        animation: none;
    }
}

/* tablet */
@media (max-width: 980px) {
    .gp-row {
        grid-template-columns: 2.4em 1fr 10.6em;
        row-gap: 0.5em;
    }

    .plate-cell {
        grid-column: 2 / -1;
        max-width: 15.6em;
    }

    .gp-row .ktp {
        grid-column: 1 / 3;
    }

    .gp-list {
        gap: 1.25em;
    }
}

/* HP */
@media (max-width: 560px) {
    .gp-row .ktp,
    .gp-row .prize {
        grid-column: 1 / -1;
    }

    .gp-row .prize {
        flex-direction: row;
        justify-content: center;
        gap: 0.6em;
        padding: 0.6em;
    }

    .ktp-fields {
        grid-template-columns: 1fr 1fr;
    }

    .ktp-fields div:nth-child(2) {
        grid-column: 1 / -1;
    }
}

/* Jarak tombol start dari kartu pengundian */
.draw-button-wrap {
    margin-top: 2.5rem;
    margin-bottom: 1.5rem;
}

.tabel {
    margin-top: 0;
}

.pemenang {
    padding-top: 370px;
}

/* Panel Pengaturan Tampilan: bentuk & animasi mengikuti sidebar #side-overlay */
#side-overlay-settings {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    z-index: 1035;
    display: flex;
    flex-direction: column;
    width: 100%;
    background-color: #fff;
    overflow: hidden;
    -webkit-overflow-scrolling: touch;
    -webkit-transform: translateX(100%) translateY(0) translateZ(0);
    transform: translateX(100%) translateY(0) translateZ(0);
    transition:
        transform 0.26s cubic-bezier(0.47, 0, 0.745, 0.715),
        -webkit-transform 0.26s cubic-bezier(0.47, 0, 0.745, 0.715);
    will-change: transform;
}

/* Kedua sidebar memakai gaya header, area konten, dan kartu yang sama */
#side-overlay .content-header,
#side-overlay-settings .content-header {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    width: 100%;
    height: 60px;
    margin: 0;
    padding: 0 18px;
    gap: 12px;
    border-bottom: 1px solid #e9ecef;
    background-color: #fff;
}

#side-overlay .settings-panel-title,
#side-overlay-settings .settings-panel-title {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
    font-size: 0.875rem;
    font-weight: 600;
    line-height: 1.3;
    color: #343a40;
    white-space: nowrap;
}

#side-overlay .settings-panel-title span,
#side-overlay-settings .settings-panel-title span {
    overflow: hidden;
    text-overflow: ellipsis;
}

#side-overlay .settings-panel-close,
#side-overlay-settings .settings-panel-close {
    flex: 0 0 auto;
    margin-left: auto;
}

/* Sidebar Pengaturan Grand Prize: header tetap di atas, konten yang bergulir */
#side-overlay .simplebar-content-wrapper {
    overflow: hidden;
}

#side-overlay .simplebar-content-wrapper > div {
    display: flex;
    flex-direction: column;
    height: 100%;
}

#side-overlay .content-side,
#side-overlay-settings > .content-side {
    flex: 1 1 auto;
    min-height: 0;
    padding: 14px;
    overflow-y: auto;
    overflow-x: hidden;
    background-color: #f8f9fa;
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, 0.2) transparent;
    -webkit-overflow-scrolling: touch;
}

#side-overlay .content-side .block,
#side-overlay-settings .content-side .block {
    width: 100%;
    margin-bottom: 0;
}

#side-overlay .block-content,
#side-overlay-settings .block-content {
    padding: 0;
}

/* Setiap kelompok pengaturan tampil sebagai kartu agar rapi dan tidak melebar */
#side-overlay .block-content > .mb-3:not(.row),
#side-overlay-settings .block-content > div > .mb-3 {
    margin-bottom: 12px !important;
    padding: 12px;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    background-color: #fff;
}

#side-overlay .block-content > .mb-3:not(.row):last-child,
#side-overlay-settings .block-content > div > .mb-3:last-child {
    margin-bottom: 0 !important;
}

#side-overlay .content-side .border.rounded {
    border-color: #e9ecef !important;
    background-color: #f8f9fa !important;
}

#side-overlay .content-side .form-label,
#side-overlay-settings .content-side .form-label {
    margin-bottom: 0.35rem;
    font-size: 0.8rem;
}

#side-overlay .content-side .form-range,
#side-overlay-settings .content-side .form-range {
    min-width: 0;
}

#side-overlay .content-side .form-control-sm,
#side-overlay .content-side .form-select-sm,
#side-overlay-settings .content-side .form-control-sm {
    min-width: 0;
    height: 32px;
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
}

#side-overlay .content-side .btn-xs,
#side-overlay-settings .content-side .btn-xs {
    padding: 0.2rem 0.45rem;
    font-size: 0.7rem;
    line-height: 1.4;
}

#side-overlay .block-content > .row {
    margin-right: 0;
    margin-left: 0;
}

#side-overlay .block-content > .row > [class*="col-"] {
    padding-right: 0;
    padding-left: 0;
}

#side-overlay .block-content > .row > [class*="col-"] + [class*="col-"] {
    padding-left: 8px;
}

#side-overlay-settings.is-open {
    -webkit-transform: translateX(0) translateY(0) translateZ(0);
    transform: translateX(0) translateY(0) translateZ(0);
}

@media (min-width: 992px) {
    #side-overlay-settings {
        width: 320px;
        box-shadow: 0 0 25px rgba(0, 0, 0, 0.15);
        -webkit-transform: translateX(110%) translateY(0) translateZ(0);
        transform: translateX(110%) translateY(0) translateZ(0);
    }
}

.page-nav-btn {
    min-width: 34px;
    min-height: 31px;
}

.page-nav-spinner {
    display: inline-block;
    width: 0.95rem;
    height: 0.95rem;
    border: 2px solid rgba(255, 255, 255, 0.45);
    border-top-color: #fff;
    border-radius: 50%;
    vertical-align: -0.15em;
    animation: page-nav-spin 0.65s linear infinite;
}

@keyframes page-nav-spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
<script>
import {
    ref,
    reactive,
    onMounted,
    onUnmounted,
    computed,
    watch,
    inject,
} from "vue";
import axios from "axios";
import confetti from "canvas-confetti";
import BackgroundSetting from "./BackgroundSetting.vue";
import PlateReel from "./PlateReel.vue";
import bgImage from "@/../images/Grobogan Civic Celebration Banner.webp";
import bgImageInitial from "@/../images/Gebyar Hadiah Pajak Grobogan.webp";

export default {
    components: {
        BackgroundSetting,
        PlateReel,
    },
    setup() {
        const appName = inject("appName");
        const drawing = ref(false);
        const shuffling = ref(false);
        const winnersReady = ref(false);
        const drawFinished = ref(false);
        const revealing = ref(false);
        let revealSession = 0;
        const pageSwitching = ref(false);
        const shufflingTimeout = ref(null);
        const drawButtonSize = ref(
            Number(localStorage.getItem("grandPrizeDrawButtonSize")) || 100,
        );
        const DEFAULT_TABLE_FONT_SIZE = 13;
        const tableFontSize = ref(
            Number(localStorage.getItem("grandPrizeTableFontSize")) ||
                DEFAULT_TABLE_FONT_SIZE,
        );

        // Kode plat daerah aktif untuk animasi pengundian. Sumber utama:
        // konfigurasi .env via window.appConfig (tersedia sejak halaman
        // dimuat), disegarkan lagi dari API wilayah bila ada. Bila belum
        // terbaca, fallback ke daftar plat Jateng.
        const injectedPlat = inject("platDaerah") || [];
        const platDaerah = ref(
            injectedPlat.length
                ? injectedPlat
                : ["H", "K", "AD", "G", "R", "AA"],
        );
        const platJateng = ["H", "K", "AD", "G", "R", "AA"];
        const disableTrigger = ref(true);

        // Batas waktu pengundian agar tombol Stop tidak terkunci bila server
        // lambat atau request menggantung. Batas dibuat besar karena request
        // pertama setelah perangkat standby bisa sangat lambat (koneksi
        // database/jaringan baru terbangun).
        const DRAW_START_DELAY = 3000;
        const DRAW_REQUEST_TIMEOUT = 45000;
        // Watchdog memberi ruang ekstra untuk pemulihan hasil (recoverDraw).
        const DRAW_WATCHDOG_TIMEOUT = DRAW_REQUEST_TIMEOUT + 20000;
        // Jeda antar reveal pemenang satu per satu.
        const REVEAL_DELAY = 550;

        const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

        // ===== Sound effect (meniru halaman referensi, Web Audio API) =====
        // Preferensi suara dibagi dengan halaman undian biasa ("undianSound").
        let AC = null;
        const soundOn = ref(
            (localStorage.getItem("undianSound") ?? "1") !== "0",
        );

        const audioCtx = () => {
            if (!AC) {
                const C = window.AudioContext || window.webkitAudioContext;
                if (C) AC = new C();
            }
            if (AC && AC.state === "suspended") AC.resume();
            return AC;
        };

        // Nada pendek dengan peluruhan eksponensial.
        // `force` memainkan nada walau suara dimatikan (untuk tombol Test).
        const tone = (f, d, type, v, f2, force = false) => {
            if (!AC || (!soundOn.value && !force)) return;
            try {
                const t = AC.currentTime;
                const o = AC.createOscillator();
                const g = AC.createGain();
                o.type = type;
                o.frequency.setValueAtTime(f, t);
                if (f2) o.frequency.exponentialRampToValueAtTime(f2, t + d);
                g.gain.setValueAtTime(v, t);
                g.gain.exponentialRampToValueAtTime(0.0001, t + d);
                o.connect(g);
                g.connect(AC.destination);
                o.start(t);
                o.stop(t + d + 0.02);
            } catch (e) {
                /* abaikan */
            }
        };

        // Nada "terompet": dua sawtooth detune lewat lowpass yang menyapu
        // naik lalu turun, dengan getar (vibrato) tipis.
        const trp = (f, t, d, v) => {
            const g = AC.createGain(),
                lp = AC.createBiquadFilter(),
                lfo = AC.createOscillator(),
                lg = AC.createGain();
            lp.type = "lowpass";
            lp.Q.value = 2;
            lp.frequency.setValueAtTime(f * 1.6, t);
            lp.frequency.linearRampToValueAtTime(f * 7, t + 0.07);
            lp.frequency.linearRampToValueAtTime(f * 4, t + d);
            g.gain.setValueAtTime(0.0001, t);
            g.gain.linearRampToValueAtTime(v, t + 0.03);
            g.gain.setValueAtTime(v, t + Math.max(0.04, d - 0.08));
            g.gain.linearRampToValueAtTime(0.0001, t + d);
            lfo.frequency.value = 5.5;
            lg.gain.setValueAtTime(0, t);
            lg.gain.linearRampToValueAtTime(f * 0.007, t + Math.min(0.4, d));
            lfo.connect(lg);
            [1, 1.004].forEach((r) => {
                const o = AC.createOscillator();
                o.type = "sawtooth";
                o.frequency.setValueAtTime(f * r, t);
                lg.connect(o.frequency);
                o.connect(lp);
                o.start(t);
                o.stop(t + d + 0.05);
            });
            lp.connect(g);
            g.connect(AC.destination);
            lfo.start(t);
            lfo.stop(t + d + 0.05);
        };

        // Nada bel: gabungan parsial sine [1, 2.01, 2.76, 5.4].
        const bel = (f, t, d, v) => {
            [
                [1, 1],
                [2.01, 0.4],
                [2.76, 0.3],
                [5.4, 0.12],
            ].forEach(([r, a]) => {
                const o = AC.createOscillator();
                const g = AC.createGain();
                o.type = "sine";
                o.frequency.value = f * r;
                g.gain.setValueAtTime(0.0001, t);
                g.gain.linearRampToValueAtTime((v || 0.06) * a, t + 0.005);
                g.gain.exponentialRampToValueAtTime(0.0001, t + d / r ** 0.4);
                o.connect(g);
                g.connect(AC.destination);
                o.start(t);
                o.stop(t + d + 0.05);
            });
        };

        // Fanfare kemenangan: "ta-ta-ta TAAAA" + akor terompet + bel,
        // semuanya terpotong bersamaan di akhir (persis referensi).
        const chime = (force = false) => {
            if (!AC || (!soundOn.value && !force)) return;
            const t = AC.currentTime + 0.08;
            const H = t + 0.5;
            const E = H + 1.1;

            // ta-ta-ta TAAAA, cukup 1X
            [
                [392, 0, 0.14],
                [523.25, 0.16, 0.14],
                [659.25, 0.32, 0.14],
            ].forEach(([f, s, d]) => trp(f, t + s, d, 0.07));
            trp(1046.5, H, E - H, 0.09);
            trp(783.99, H, E - H, 0.045);
            trp(659.25, H, E - H, 0.04);
            trp(523.25, H, E - H, 0.04);
            trp(261.63, H, E - H, 0.05);
            [
                [1046.5, 0, 0.08],
                [2093, 0, 0.05],
                [1568, 0.08, 0.05],
                [2093, 0.16, 0.05],
                [2637, 0.24, 0.05],
                [3136, 0.32, 0.05],
                [4186, 0.4, 0.05],
            ].forEach(([f, s, v]) => bel(f, H + s, E - H - s, v));
        };

        // Bunyi "tek-tek" selama reel berputar.
        let tickTimer = null;

        const startTick = () => {
            if (tickTimer) return;
            // Tick pertama langsung dimainkan (bukan menunggu interval).
            tone(900 + Math.random() * 500, 0.03, "triangle", 0.014);
            tickTimer = setInterval(() => {
                tone(900 + Math.random() * 500, 0.03, "triangle", 0.014);
            }, 70);
        };

        const stopTick = () => {
            if (!tickTimer) return;
            clearInterval(tickTimer);
            tickTimer = null;
        };

        // Confetti 2 detik dengan arah berbeda-beda (sama dengan halaman
        // undian biasa): kiri, kanan, tengah bawah, kedua sudut bawah, dan
        // hujan dari tengah atas.
        let confettiTimers = [];

        // Penghitung pendaratan piringan nopol untuk sinkronisasi confetti:
        // confetti menunggu SEMUA piringan benar-benar berhenti bergerak.
        let plateLandedCount = 0;
        let confettiPending = false;

        const fireConfetti = () => {
            const shots = [
                { angle: 60, origin: { x: 0, y: 0.7 }, spread: 70 },
                { angle: 120, origin: { x: 1, y: 0.7 }, spread: 70 },
                { angle: 90, origin: { x: 0.5, y: 1 }, spread: 100 },
                { angle: 45, origin: { x: 0, y: 0.9 }, spread: 80 },
                { angle: 135, origin: { x: 1, y: 0.9 }, spread: 80 },
                { angle: 90, origin: { x: 0.5, y: 0 }, spread: 120 },
                { angle: 60, origin: { x: 0, y: 0.7 }, spread: 100 },
                { angle: 120, origin: { x: 1, y: 0.7 }, spread: 100 },
            ];

            // 8 tembakan x 250ms = total efek 2 detik.
            shots.forEach((shot, i) => {
                confettiTimers.push(
                    setTimeout(() => {
                        confetti({
                            particleCount: 90,
                            angle: shot.angle,
                            spread: shot.spread,
                            startVelocity: 55,
                            scalar: 1.1,
                            origin: shot.origin,
                        });
                    }, i * 250),
                );
            });
        };

        const toggleSound = () => {
            soundOn.value = !soundOn.value;
            localStorage.setItem("undianSound", soundOn.value ? "1" : "0");
            if (soundOn.value) audioCtx();
        };

        // Buka kunci AudioContext sejak interaksi pertama di halaman:
        // resume() bersifat asinkron, jadi bila baru dipanggil saat tombol
        // Start diklik, beberapa bunyi awal bisa hilang.
        const unlockAudio = () => {
            audioCtx();
            window.removeEventListener("pointerdown", unlockAudio);
            window.removeEventListener("keydown", unlockAudio);
        };
        window.addEventListener("pointerdown", unlockAudio);
        window.addEventListener("keydown", unlockAudio);

        // Demo singkat untuk tombol Test Suara: bunyi "tek-tek" slot +
        // fanfare kemenangan. Dimainkan walau suara sedang dimatikan.
        const testSound = () => {
            audioCtx();
            [0, 100, 200, 300].forEach((ms) =>
                setTimeout(
                    () =>
                        tone(
                            900 + Math.random() * 500,
                            0.03,
                            "triangle",
                            0.02,
                            null,
                            true,
                        ),
                    ms,
                ),
            );
            setTimeout(() => chime(true), 450);
        };

        const quantity = ref(1);

        const selectedCategoryId = ref("");
        const selectedPrizeIds = ref([]);
        const prizeOptions = ref([]);

        // Data kecamatan aktif (id -> nama) untuk kolom Kecamatan.
        const kecamatanNames = ref({});
        const kecamatanList = ref([]);

        const extractGram = (name) => {
            const cleaned = String(name || "").replace(",", ".");
            const match = cleaned.match(/([0-9]+(?:\.[0-9]+)?)/);
            return match ? parseFloat(match[1]) : 0;
        };

        const sortPrizeByWeight = (a, b) => {
            return extractGram(b.name) - extractGram(a.name);
        };

        // Aturan urut hadiah: "motor" dulu, lalu nilai terbesar
        // (Tabungan 15 jt sebelum 10 jt, dst). Dipakai untuk urutan hadiah
        // setelah Simpan DAN pengurutan baris hasil undian agar konsisten.
        const prizeSortKey = (name) =>
            String(name || "")
                .toLowerCase()
                .includes("motor")
                ? 0
                : 1;
        const comparePrize = (a, b) => {
            const rankDiff = prizeSortKey(a) - prizeSortKey(b);
            if (rankDiff !== 0) return rankDiff;
            return extractGram(b || "") - extractGram(a || "");
        };

        // Samarkan bagian tengah ke belakang alamat: hanya bagian depan
        // alamat yang terlihat, sisanya diganti tanda bintang.
        const ALAMAT_VISIBLE_CHARS = 16;
        const maskAlamat = (alamat) => {
            const text = String(alamat ?? "")
                .trim()
                .replace(/\s+/g, " ");
            if (!text) return "-";
            // Saat animasi pengacakan, alamat berupa tanda tanya: biarkan.
            if (/^\?+$/.test(text)) return text;
            if (text.length <= ALAMAT_VISIBLE_CHARS) return text;

            let front = text.slice(0, ALAMAT_VISIBLE_CHARS);
            const lastSpace = front.lastIndexOf(" ");
            if (lastSpace >= 8) front = front.slice(0, lastSpace);

            return `${front.trimEnd()}***`;
        };

        // Baris "exp" plat nomor: masa berlaku STNK dalam format "MM · YY"
        // (contoh "08 · 27") mengikuti referensi grand-prize-1-halaman.
        // Nilai ini TIDAK diacak saat pengundian — tampil placeholder
        // "XX · XX" sebelum baris di-reveal, lalu nilai aslinya.
        const formatExp = (value) => {
            const match = String(value ?? "").match(/(\d{4})-(\d{2})/);
            return match ? `${match[2]} · ${match[1].slice(2)}` : "";
        };

        // Nama kecamatan dari id_kecamatan pada data kendaraan.
        const resolveKecamatan = (item) => {
            const id = item?.id_kecamatan;
            if (id !== null && id !== undefined && id !== "") {
                const nama = kecamatanNames.value[String(id)];
                return nama || item?.lokasi || String(id);
            }
            return item?.lokasi || "-";
        };

        const prizeCategories = computed(() => {
            const categories = [];
            prizeOptions.value.forEach((item) => {
                const category = item.prize_category || {
                    id: null,
                    name: "Tanpa Kategori",
                };
                if (!categories.some((c) => c.id === category.id)) {
                    categories.push({ id: category.id, name: category.name });
                }
            });
            return categories;
        });

        const filteredPrizes = computed(() => {
            if (!selectedCategoryId.value) {
                return [];
            }
            return prizeOptions.value
                .filter(
                    (item) =>
                        item.prize_category?.id === selectedCategoryId.value,
                )
                .sort(sortPrizeByWeight);
        });

        const selectedPrizeNames = computed(() => {
            return selectedPrizeIds.value
                .map((id) => prizeOptions.value.find((item) => item.id === id))
                .filter(Boolean)
                .sort(sortPrizeByWeight)
                .map((item) => item.name);
        });

        const selectedPrizeLabel = computed(() => {
            if (selectedPrizeNames.value.length === 0) return "-";
            return selectedPrizeNames.value.join(", ");
        });

        const maxQuantity = computed(() => {
            return selectedPrizeIds.value.reduce((sum, id) => {
                const prize = prizeOptions.value.find((item) => item.id === id);
                return sum + (prize ? prize.available_quantity : 0);
            }, 0);
        });

        const onCategoryChange = () => {
            if (!selectedCategoryId.value) {
                selectedPrizeIds.value = [];
                quantity.value = 1;
                return;
            }

            // Pilih semua hadiah dalam kategori sebagai default (bisa di-uncheck manual).
            selectedPrizeIds.value = filteredPrizes.value.map(
                (item) => item.id,
            );

            // Jumlah pemenang fleksibel: default mengikuti stok terpilih.
            quantity.value = maxQuantity.value || 1;
        };

        const settingError = computed(() => {
            if (!selectedCategoryId.value) return "Pilih kategori hadiah.";
            if (selectedPrizeIds.value.length === 0)
                return "Pilih minimal 1 jenis hadiah.";
            if (!quantity.value || parseInt(quantity.value) < 1)
                return "Jumlah pemenang minimal 1.";

            if (parseInt(quantity.value) > maxQuantity.value) {
                return `Jumlah (${quantity.value}) melebihi stok tersedia (${maxQuantity.value}).`;
            }
            return "";
        });

        // Background dinamis: poster (sebelum setup) & halaman grand prize punya
        // gambar + ukuran sendiri dari sidebar. Bila belum diatur, pakai bawaan.
        const customBgImages = reactive({ poster: "", page: "" });
        const customBgSizes = reactive({ poster: 100, page: 100 });

        const onBackgroundChange = ({ mode, url }) => {
            if (mode in customBgImages) customBgImages[mode] = url || "";
        };

        const onBackgroundSizeChange = ({ mode, size }) => {
            if (mode in customBgSizes) customBgSizes[mode] = size;
        };

        const currentBgImage = computed(() => {
            if (form.quantity > 0) return customBgImages.page || bgImage;
            return customBgImages.poster || bgImageInitial;
        });

        const currentBgSize = computed(() =>
            form.quantity > 0 ? customBgSizes.page : customBgSizes.poster,
        );

        const backgroundStyle = computed(() => ({
            backgroundImage: `url(${currentBgImage.value})`,
            backgroundSize: `${currentBgSize.value}%`,
        }));

        // Panel "Pengaturan Tampilan" terpisah (tombol di kanan pembuka sidebar).
        // Pengaturan tampilan kini hanya ada di panel ini.
        const showDisplaySettings = ref(false);
        const settingsPanel = ref(null);

        const toggleDisplaySettings = () => {
            showDisplaySettings.value = !showDisplaySettings.value;

            // Tutup sidebar utama agar tidak bertumpuk dengan panel.
            if (showDisplaySettings.value) {
                const el = document.getElementById("page-container");
                if (el) {
                    el.classList.remove("side-overlay-o");
                }
            }
        };

        // Tutup panel saat pengguna mengklik di luar panel, sama seperti
        // sidebar Pengaturan Grand Prize (#side-overlay) yang memakai page overlay.
        const closeDisplaySettingsOnOutsideClick = (event) => {
            if (!showDisplaySettings.value) return;

            const target = event.target;
            if (!(target instanceof Element)) return;
            if (settingsPanel.value?.contains(target)) return;
            // Jangan tutup saat tombol pembuka panel yang diklik.
            if (target.closest("[data-settings-toggle]")) return;

            showDisplaySettings.value = false;
        };

        const form = reactive({
            prize_ids: [],
            prizeNames: [],
            quantity: 0,
            prizeKey: "",
        });

        const undianList = ref([]);
        const winnerList = ref([]);

        const createPrizeKey = (ids = selectedPrizeIds.value) => {
            return [...ids].sort((a, b) => Number(a) - Number(b)).join("|");
        };

        const drawButtonLabel = computed(() => {
            if (revealing.value) return "Menampilkan Pemenang...";
            if (shuffling.value) return "Sedang Mengacak";
            if (drawing.value) return "Stop Pengundian!";
            return "Start Pengundian!";
        });

        const drawButtonClass = computed(() => {
            if (shuffling.value || revealing.value) return "btn-warning";
            if (drawing.value) return "btn-danger";
            return "btn-success";
        });

        const drawButtonDisabled = computed(() => {
            // Saat pengundian berjalan tombol Stop harus selalu bisa diklik,
            // jadi kunci hanya berlaku sebelum pengundian dimulai.
            return (
                (disableTrigger.value && !drawing.value) ||
                shuffling.value ||
                drawFinished.value ||
                revealing.value
            );
        });

        const drawButtonStyle = computed(() => {
            const scale =
                Math.min(
                    Math.max(Number(drawButtonSize.value) || 100, 80),
                    220,
                ) / 100;

            return {
                fontSize: `${1.15 * scale}rem`,
                minHeight: `${52 * scale}px`,
                padding: `${12 * scale}px ${28 * scale}px`,
                minWidth: `${230 * scale}px`,
                lineHeight: "1.15",
            };
        });

        watch(drawButtonSize, (value) => {
            const normalized = Math.min(
                Math.max(Number(value) || 100, 80),
                220,
            );
            if (normalized !== value) {
                drawButtonSize.value = normalized;
                return;
            }
            localStorage.setItem(
                "grandPrizeDrawButtonSize",
                String(normalized),
            );
        });

        watch(tableFontSize, (value) => {
            const normalized = Math.min(
                Math.max(Number(value) || DEFAULT_TABLE_FONT_SIZE, 8),
                28,
            );
            if (normalized !== value) {
                tableFontSize.value = normalized;
                return;
            }
            localStorage.setItem("grandPrizeTableFontSize", String(normalized));
        });

        const resetTableFontSize = () => {
            tableFontSize.value = DEFAULT_TABLE_FONT_SIZE;
            localStorage.setItem(
                "grandPrizeTableFontSize",
                String(DEFAULT_TABLE_FONT_SIZE),
            );
        };

        // Jarak undian dari atas (px), tersimpan permanen di localStorage.
        const DEFAULT_UNDIAN_TOP = 24;
        const storedUndianTop = localStorage.getItem(
            "grandPrizeUndianTopOffset",
        );
        const undianTopOffset = ref(
            storedUndianTop !== null
                ? Number(storedUndianTop)
                : DEFAULT_UNDIAN_TOP,
        );

        watch(undianTopOffset, (value) => {
            const normalized = Math.min(Math.max(Number(value) || 0, 0), 500);
            if (normalized !== value) {
                undianTopOffset.value = normalized;
                return;
            }
            localStorage.setItem(
                "grandPrizeUndianTopOffset",
                String(normalized),
            );
        });

        const resetUndianTopOffset = () => {
            undianTopOffset.value = DEFAULT_UNDIAN_TOP;
            localStorage.setItem(
                "grandPrizeUndianTopOffset",
                String(DEFAULT_UNDIAN_TOP),
            );
        };

        // Ukuran kartu via em: font kartu = tableFontSize / 0.875 karena teks
        // isi kartu (dd KTP) memakai 0.875em, sehingga ukuran font tabel yang
        // diatur lewat slider tetap = tableFontSize.
        const tableAppearanceStyle = computed(() => ({
            "--gp-card-fs": `${(
                Math.min(
                    Math.max(
                        Number(tableFontSize.value) || DEFAULT_TABLE_FONT_SIZE,
                        8,
                    ),
                    28,
                ) / 0.875
            ).toFixed(2)}px`,
        }));

        const clearShufflingTimeout = () => {
            if (!shufflingTimeout.value) return;
            clearTimeout(shufflingTimeout.value);
            shufflingTimeout.value = null;
        };

        // ===== Pengacakan teks KTP saat mengundian =====
        // Nama, alamat, kecamatan, dan roda berganti-ganti nilainya sejak
        // Start. Nilainya realistis tapi BUKAN data pemenang: diambil dari
        // stok sampel kendaraan lain di database (endpoint get-data/scramble,
        // bukan data pemenang) — persis semangat halaman per koordinator yang
        // memakai kombinasi nama orang Indonesia. Bila stok gagal dimuat,
        // jatuh ke generator nama/jalan acak. Baris yang sudah di-reveal
        // berhenti diacak dan menampilkan data aslinya.
        const scrambleTimer = ref(null);
        const scrambleStock = ref([]);

        // Generator cadangan: kombinasi nama depan (+ tengah) + belakang
        // orang Indonesia yang umum, seperti randomName() di App.vue.
        const NAMA_DEPAN = [
            "AGUS",
            "AHMAD",
            "ANDI",
            "ANTO",
            "ARIS",
            "BAMBANG",
            "BAYU",
            "BUDI",
            "DARMAWATI",
            "DEWI",
            "DIMAS",
            "DUDI",
            "DWI",
            "EGI",
            "EKO",
            "ENDANG",
            "FAJAR",
            "FARIDA",
            "FITRI",
            "GALIH",
            "GUNTUR",
            "HADI",
            "HENDRA",
            "HERI",
            "IDA",
            "INDAH",
            "IRWAN",
            "JOKO",
            "KARTIKA",
            "LATIF",
            "LESTARI",
            "LILIS",
            "MARNI",
            "MARYANI",
            "MUHAMMAD",
            "NANIK",
            "NUR",
            "OKTA",
            "PUTRI",
            "RAHMA",
            "RATNA",
            "RINA",
            "RINI",
            "RUDY",
            "SARI",
            "SITI",
            "SLAMET",
            "SRI",
            "SUCI",
            "SUDARNO",
            "SUGIYONO",
            "SUKARNO",
            "SULISTYOWATI",
            "SUMARNO",
            "SUNARTO",
            "SUPARNO",
            "SUSETYO",
            "SUTARSIH",
            "SUWANDI",
            "TAUFIK",
            "TEGUH",
            "TRI",
            "UMI",
            "WAHYU",
            "WARTI",
            "YATI",
            "YULIANI",
            "YUNI",
            "ZAINAL",
        ];
        const NAMA_BELAKANG = [
            "ADI",
            "ANTONI",
            "ARDI",
            "ASIH",
            "BASUKI",
            "CAHYONO",
            "DARMAWAN",
            "FEBRIANTO",
            "HANDOKO",
            "HARYANTO",
            "HIDAYAT",
            "HUTABARAT",
            "ISWANTO",
            "KURNIAWAN",
            "KUSUMA",
            "LAKSANA",
            "LUTFIANA",
            "MAULANA",
            "NUGRAHA",
            "NUGROHO",
            "PALUPI",
            "PAMUNGKAS",
            "PRASETYO",
            "PRATAMA",
            "PUJIASTUTI",
            "PURWANTO",
            "RAHARDJO",
            "RAHAYU",
            "RAHMAT",
            "SAPUTRA",
            "SARSWATI",
            "SETIAWAN",
            "SIHOMBING",
            "SIMBOLON",
            "SITOMPUL",
            "SUGIYANTO",
            "SUHARTO",
            "SUMARNI",
            "SUPRAPTI",
            "SURATMAN",
            "SURYANA",
            "SUSANTI",
            "SUSILOWATI",
            "SUTARNO",
            "SUWARTI",
            "TANJUNG",
            "WAHYUNI",
            "WIBOWO",
            "WIDAYATI",
            "WIDODO",
            "WIJAYA",
            "WINARTO",
            "YULIASTUTI",
            "YUSUF",
        ];
        const JALAN = [
            "Pandanaran",
            "Merdeka",
            "Ahmad Yani",
            "Slamet Riyadi",
            "Gatot Subroto",
            "Diponegoro",
            "Pemuda",
            "Kalitanjung",
            "M.T. Haryono",
            "Cendrawasih",
            "Kenari",
            "Melati",
            "Anggrek",
            "Kenanga",
            "Manggis",
            "Salam",
            "Rejosari",
            "Purwodiningatan",
        ];
        const LINGKUNGAN = [
            "RT 03/RW 05",
            "Kel. Sukamaju",
            "Perum Griya Asri",
            "Dusun Krajan",
            "Perum Pollux",
            "Kel. Bangunharjo",
        ];

        const pick = (list) => list[Math.floor(Math.random() * list.length)];

        const randomName = () => {
            const parts = [pick(NAMA_DEPAN)];
            if (Math.random() < 0.4) parts.push(pick(NAMA_DEPAN));
            parts.push(pick(NAMA_BELAKANG));
            return parts.join(" ");
        };

        const randomAlamat = () => {
            const parts = [`Jl. ${pick(JALAN)}`];
            if (Math.random() < 0.5)
                parts.push(`No. ${1 + ((Math.random() * 99) | 0)}`);
            if (Math.random() < 0.3) parts.push(pick(LINGKUNGAN));
            return parts.join(" ");
        };

        // Muat stok data acakan dari database: sampel kendaraan yang belum
        // pernah menang, jadi terasa nyata tanpa membocorkan pemenang.
        const getScrambleStock = async () => {
            try {
                const response = await axios.get("/api/get-data/scramble", {
                    timeout: 15000,
                });
                if (Array.isArray(response.data)) {
                    scrambleStock.value = response.data.filter(
                        (item) => item && item.nama,
                    );
                }
            } catch (error) {
                console.error("Gagal memuat stok data acakan:", error);
            }
        };

        const startScramble = () => {
            if (scrambleTimer.value) return;
            scrambleTimer.value = setInterval(() => {
                undianList.value.forEach((row) => {
                    if (row.revealed) return;
                    const stock = scrambleStock.value;
                    if (stock.length) {
                        // Stok dari database: nama, alamat, kecamatan, dan
                        // roda kendaraan nyata yang bukan pemenang.
                        const src = stock[(Math.random() * stock.length) | 0];
                        row.dispNama = src.nama || randomName();
                        row.dispAlamat =
                            maskAlamat(src.alamat) || randomAlamat();
                        row.dispLokasi =
                            resolveKecamatan(src) ||
                            pick(kecamatanList.value) ||
                            randomName();
                        row.dispRoda = src.roda || pick(["2", "4"]);
                    } else {
                        // Stok belum/gagal termuat: generator nama acak.
                        row.dispNama = randomName();
                        row.dispAlamat = maskAlamat(randomAlamat());
                        row.dispLokasi =
                            pick(kecamatanList.value) || randomName();
                        row.dispRoda = pick(["2", "4"]);
                    }
                });
            }, 80);
        };

        const stopScramble = () => {
            if (!scrambleTimer.value) return;
            clearInterval(scrambleTimer.value);
            scrambleTimer.value = null;
        };

        const resetDrawState = () => {
            clearShufflingTimeout();
            stopScramble();
            revealSession += 1;
            revealing.value = false;
            shuffling.value = false;
            winnersReady.value = false;
            drawing.value = false;
            drawFinished.value = false;
            winnerList.value = [];
            // Batalkan sisa tembakan confetti sesi sebelumnya.
            confettiTimers.splice(0).forEach(clearTimeout);
            confettiPending = false;
            plateLandedCount = 0;
        };

        const initUndianList = () => {
            undianList.value = [];
            for (let i = 0; i < parseInt(form.quantity); i++) {
                undianList.value.push({
                    id: i + 1,
                    // Placeholder pola piringan "XX 0000 XXX" + "XXXXXXXX"
                    // untuk kolom teks, konsisten dengan halaman undian biasa.
                    kodeWilayah: "XX",
                    angka: "0000",
                    kodeSeri: "XXX",
                    exp: "XX · XX",
                    revealed: false,
                    id_kendaraan: "",
                    // Field tampilan (disp*): diisi pengacak saat mengundian,
                    // diganti data asli saat baris ter-reveal.
                    dispNama: "XXXXXXXX",
                    dispAlamat: "XXXXXXXX",
                    dispLokasi: "XXXXXXXX",
                    dispRoda: "XXXXXXXX",
                    nama: "XXXXXXXX",
                    alamat: "XXXXXXXX",
                    roda: "XXXXXXXX",
                    lokasi: "XXXXXXXX",
                    prize: {
                        id: "",
                        name: "?",
                    },
                });
            }
        };

        // Reset baris ke tampilan placeholder sebelum reveal. Kolom Hadiah
        // TIDAK direset: hadiah sudah dipasang sejak Simpan pengaturan.
        const startDrawingRow = (row) => {
            row.revealed = false;
            row.kodeWilayah = "XX";
            row.angka = "0000";
            row.kodeSeri = "XXX";
            row.exp = "XX · XX";
            row.dispNama = "XXXXXXXX";
            row.dispAlamat = "XXXXXXXX";
            row.dispLokasi = "XXXXXXXX";
            row.dispRoda = "XXXXXXXX";
        };

        /**
         * Coba pulihkan hasil undian yang mungkin sudah tersimpan di server
         * (request terputus setelah backend menyimpan pemenang, mis. perangkat
         * standby lama). Endpoint /api/draw/recent mengembalikan hasil undian
         * yang baru saja tersimpan dalam bentuk respons yang sama.
         */
        const recoverDraw = async () => {
            try {
                const response = await axios.get("api/draw/recent", {
                    timeout: 10000,
                });
                const items = response.data?.data;

                if (
                    response.data?.recovered &&
                    Array.isArray(items) &&
                    items.length > 0
                ) {
                    winnerList.value = items;
                    winnersReady.value = true;
                    getOptionsPrize();
                    alert(
                        "Koneksi ke server sempat terputus, tetapi hasil undian sudah tersimpan di server dan kini ditampilkan.",
                    );
                    return true;
                }
            } catch (error) {
                console.error("Gagal memulihkan hasil undian:", error);
            }

            return false;
        };

        const pickWinner = async () => {
            disableTrigger.value = true;

            const controller = new AbortController();
            const timeoutId = setTimeout(
                () => controller.abort(),
                DRAW_REQUEST_TIMEOUT,
            );

            try {
                const response = await axios.post(
                    "api/draw/pick",
                    {
                        prize_ids: form.prize_ids,
                        quantity: parseInt(form.quantity),
                        is_global: true,
                        is_demo: false,
                    },
                    {
                        signal: controller.signal,
                        timeout: DRAW_REQUEST_TIMEOUT,
                    },
                );

                winnerList.value = response.data.data;
                winnersReady.value = true;
                getOptionsPrize();
                disableTrigger.value = false;
                return true;
            } catch (error) {
                console.error("Gagal update:", error.response?.data || error);

                // Sesi/koneksi kedaluwarsa: muat ulang halaman agar koneksi baru.
                if (error.response?.status === 419) {
                    alert("Sesi telah berakhir. Halaman akan dimuat ulang.");
                    setTimeout(() => window.location.reload(), 1200);
                    return false;
                }

                // Koneksi lambat/terputus: hasil bisa saja sudah tersimpan di
                // server (backend tetap memproses sampai selesai). Coba pulihkan
                // dulu sebelum menampilkan error.
                const connectionIssue =
                    error.code === "ERR_CANCELED" ||
                    error.code === "ECONNABORTED" ||
                    (error.response?.status ?? 0) >= 500 ||
                    !error.response;

                if (connectionIssue) {
                    const recovered = await recoverDraw();

                    if (recovered) {
                        disableTrigger.value = false;
                        return true;
                    }
                }

                disableTrigger.value = false;
                resetDrawState();

                const timedOut =
                    error.code === "ERR_CANCELED" ||
                    error.code === "ECONNABORTED";
                alert(
                    timedOut
                        ? "Pengundian melebihi batas waktu. Silakan coba lagi."
                        : error.response?.data.message ||
                              "Terjadi kesalahan saat pengundian.",
                );
                return false;
            } finally {
                clearTimeout(timeoutId);
            }
        };

        const parseWinner = () => {
            const list = winnerList.value.map((item, index) => {
                const parts = item.no_polisi.match(/[a-zA-Z]+|[0-9]+/g) || [
                    "XX",
                    "0000",
                    "XX",
                ];
                const kodeWilayah = parts[0] || "XX";
                const angka = parts[1] || "0000";
                const kodeSeri = parts.slice(2).join("") || "XX";

                return {
                    id: index + 1,
                    kodeWilayah,
                    angka,
                    kodeSeri,
                    exp: formatExp(item.tgl_akhir_stnk),
                    revealed: false,
                    id_kendaraan: item.id_kendaraan,
                    dispNama: "XXXXXXXX",
                    dispAlamat: "XXXXXXXX",
                    dispLokasi: "XXXXXXXX",
                    dispRoda: "XXXXXXXX",
                    nama: item.nama,
                    alamat: item.alamat,
                    roda: item.roda,
                    lokasi: resolveKecamatan(item),
                    prize: item.prize || { name: selectedPrizeLabel.value },
                };
            });

            // Urutan baris: motor dulu, lalu tabungan/hadiah lain berdasarkan
            // nilai terbesar — aturan yang sama dengan urutan hadiah yang
            // tampil sejak Simpan (comparePrize).
            list.sort((a, b) => comparePrize(a.prize?.name, b.prize?.name));

            // reindex ids
            undianList.value = list.map((it, idx) => ({ ...it, id: idx + 1 }));
        };

        // Reveal pemenang satu per satu dengan jarak waktu agar terasa
        // seperti mesin slot yang berhenti baris demi baris.
        const revealWinners = async () => {
            const session = ++revealSession;
            revealing.value = true;

            for (let i = 0; i < undianList.value.length; i++) {
                if (session !== revealSession) return false;

                await delay(REVEAL_DELAY);

                if (session !== revealSession) return false;
                undianList.value[i].revealed = true;

                // Nada pendek bernada naik mengikuti urutan baris (referensi).
                tone(520 + i * 14, 0.08, "triangle", 0.05);
            }

            return session === revealSession;
        };

        const drawHandle = async () => {
            if (drawButtonDisabled.value) return;

            if (!drawing.value) {
                // start: show drawing UI immediately and set a 3s "shuffling" lock
                drawing.value = true;
                shuffling.value = true;
                winnersReady.value = false;
                undianList.value.forEach((row) => startDrawingRow(row));

                // Acak teks KTP sejak tombol Start ditekan (huruf acak dulu,
                // berpindah ke pool pemenang begitu data server masuk).
                startScramble();

                // Audio: siapkan context; bunyi "tek-tek" mengikuti reel.
                audioCtx();
                startTick();

                const unlockStopButton = () => {
                    if (!winnersReady.value) return;
                    shuffling.value = false;
                    shufflingTimeout.value = null;
                };

                // keep button disabled and text changed for at least 3 seconds
                clearShufflingTimeout();
                shufflingTimeout.value = setTimeout(
                    unlockStopButton,
                    DRAW_START_DELAY,
                );

                const startedAt = Date.now();
                let picked = false;

                try {
                    // Watchdog mencegah tombol Stop terkunci permanen bila
                    // request ke server macet.
                    picked = await Promise.race([
                        pickWinner(),
                        delay(DRAW_WATCHDOG_TIMEOUT).then(() => false),
                    ]);
                } catch (error) {
                    console.error("Gagal memulai pengundian:", error);
                    picked = false;
                }

                // Pastikan animasi pengacakan tampil minimal DRAW_START_DELAY.
                const elapsed = Date.now() - startedAt;
                if (elapsed < DRAW_START_DELAY) {
                    await delay(DRAW_START_DELAY - elapsed);
                }

                if (!picked) {
                    // Gagal / melewati batas waktu: bersihkan state dan
                    // buka kunci tombol agar bisa dicoba lagi.
                    stopTick();
                    resetDrawState();
                    return;
                }

                clearShufflingTimeout();
                if (!drawing.value) return;
                unlockStopButton();

                // Peta pemenang + hadiah dipasang begitu data siap: plat nomor
                // tahu target mendaratnya dan urutan baris final (motor dulu,
                // baru tabungan). Pengacakan teks KTP sudah berjalan sejak
                // Start dan memakai stok data acakan, bukan data pemenang.
                parseWinner();

                // Segarkan stok acakan: pemenang baru otomatis dikeluarkan.
                getScrambleStock();
            } else {
                // stop
                // Jangan proses hasil sebelum data pemenang benar-benar siap.
                if (!winnersReady.value) return;

                // Benturan tombol stop + bunyi klik (persis referensi).
                tone(140, 0.2, "sine", 0.14, 50);
                tone(1700, 0.04, "square", 0.02);
                stopTick();

                // Tidak perlu parseWinner(): peta pemenang + hadiah sudah
                // dipasang saat data siap. Baris terus teracak sampai
                // masing-masing baris di-reveal oleh revealWinners.

                // Aktifkan penghitung pendaratan SEBELUM reveal: event
                // "landed" baris-baris awal datang selama loop reveal
                // berjalan, bukan setelahnya.
                plateLandedCount = 0;
                confettiPending = true;

                // Reveal pemenang satu per satu; selama reveal, reel nopol
                // berhenti baris demi baris.
                const completed = await revealWinners();
                if (!completed) return;

                disableTrigger.value = true;
                drawFinished.value = true;
                drawing.value = false;
                // Semua baris sudah ter-reveal: hentikan pengacakan teks.
                stopScramble();
                // Confetti & chime menunggu piringan nopol baris TERAKHIR
                // benar-benar selesai mendarat (dipicu onPlateLanded).
            }
        };

        // Dipanggil PlateReel tiap piringan selesai mendarat. Confetti &
        // chime hanya meledak setelah SEMUA piringan benar-benar berhenti
        // bergerak — bukan saat barisnya saja sudah ter-reveal.
        const onPlateLanded = () => {
            if (!confettiPending) return;

            plateLandedCount += 1;
            if (plateLandedCount >= undianList.value.length) {
                confettiPending = false;
                // 300ms: menunggu animasi flash baris terakhir tuntas agar
                // seluruh data terlihat statis sempurna sebelum confetti.
                confettiTimers.push(
                    setTimeout(() => {
                        fireConfetti();
                        chime();
                    }, 300),
                );
            }
        };

        const spaceKeyHandler = (e) => {
            const isSpace =
                e.code === "Space" || e.key === " " || e.key === "Spacebar";
            if (!isSpace) return;
            const active =
                document.activeElement && document.activeElement.tagName;
            if (
                active === "INPUT" ||
                active === "TEXTAREA" ||
                active === "SELECT" ||
                document.activeElement?.isContentEditable
            )
                return;
            e.preventDefault();
            if (drawButtonDisabled.value) return;
            drawHandle();
        };

        const saveSetting = async () => {
            if (settingError.value) {
                alert(settingError.value);
                return;
            }

            form.prize_ids = [...selectedPrizeIds.value];
            form.prizeNames = [...selectedPrizeNames.value];
            form.quantity = quantity.value;
            form.prizeKey = createPrizeKey();

            const el = document.getElementById("page-container");
            if (el) {
                el.classList.remove("side-overlay-o");
            }

            // Simpan = commit pengaturan baru: tabel di-refresh dan tombol
            // Start dibuka lagi, termasuk setelah undian selesai (alur undian
            // bertahap 1 per 1 mengandalkan Simpan untuk lanjut ke undian
            // berikutnya).
            resetDrawState();
            initUndianList();

            // Hadiah langsung tampil di tabel begitu Simpan: susun sesuai
            // stok hadiah yang dipilih, urut motor dulu lalu tabungan/hadiah
            // lain dari nilai terbesar (aturan yang sama dengan pengurutan
            // hasil undian, sehingga urutan tidak berubah setelah undi).
            // Kartu Hadiah menampilkan deskripsi hadiah; urutan tetap
            // dihitung dari nama hadiah (comparePrize).
            const pool = [];
            selectedPrizeIds.value
                .map((id) => prizeOptions.value.find((item) => item.id === id))
                .filter(Boolean)
                .forEach((prize) => {
                    const count = Math.min(
                        prize.available_quantity || 0,
                        Math.max(quantity.value - pool.length, 0),
                    );
                    for (let i = 0; i < count; i++) {
                        pool.push({
                            name: prize.name,
                            description: prize.description || "",
                        });
                    }
                });
            pool.sort((a, b) => comparePrize(a.name, b.name));
            undianList.value.forEach((row, i) => {
                if (pool[i]) {
                    row.prize = {
                        id: "",
                        name: pool[i].name,
                        description: pool[i].description,
                    };
                }
            });

            disableTrigger.value = false;
        };

        const hasSetup = computed(() => {
            return form.prize_ids.length > 0 && form.quantity > 0;
        });

        const trigger = () => {
            // optional click trigger
        };

        const goToRegularDraw = () => {
            if (pageSwitching.value) return;
            pageSwitching.value = true;
            window.location.href = "/";
        };

        const getOptionsPrize = async () => {
            try {
                const response = await axios.get("/api/get-data/prize");
                prizeOptions.value = response.data;
            } catch (error) {
                console.error("Gagal memuat data hadiah:", error);
            }
        };

        // Muat daftar kecamatan aktif untuk kolom Kecamatan.
        const getWilayah = async () => {
            try {
                const response = await axios.get("/api/get-data/wilayah");
                const list = Array.isArray(response.data?.kecamatan)
                    ? response.data.kecamatan
                    : [];
                const map = {};
                list.forEach((kec) => {
                    if (kec?.id !== undefined && kec?.id !== null) {
                        map[String(kec.id)] = kec.nama;
                    }
                });
                kecamatanNames.value = map;
                kecamatanList.value = list
                    .map((kec) => kec.nama)
                    .filter(Boolean);

                // Kode plat daerah aktif untuk animasi pengundian.
                const platKode = response.data?.plat_kode;
                if (Array.isArray(platKode) && platKode.length) {
                    platDaerah.value = platKode;
                }
            } catch (error) {
                console.error("Gagal memuat data wilayah:", error);
            }
        };

        // Keep-alive: ping ringan tiap 5 menit agar koneksi ke server tetap
        // hidup selama perangkat dibiarkan standby, sehingga pengundian
        // pertama setelah standby tidak lambat/terputus.
        let keepAliveTimer = null;

        const startKeepAlive = () => {
            if (keepAliveTimer) return;
            keepAliveTimer = setInterval(
                () => {
                    axios
                        .get("/api/get-data/prize", { timeout: 15000 })
                        .catch(() => {});
                },
                5 * 60 * 1000,
            );
        };

        const stopKeepAlive = () => {
            if (!keepAliveTimer) return;
            clearInterval(keepAliveTimer);
            keepAliveTimer = null;
        };

        onMounted(async () => {
            document.addEventListener(
                "click",
                closeDisplaySettingsOnOutsideClick,
            );
            getOptionsPrize();
            getWilayah();
            getScrambleStock();
            startKeepAlive();
            window.addEventListener("keydown", spaceKeyHandler);
        });

        onUnmounted(() => {
            clearShufflingTimeout();
            stopScramble();
            undianList.value.forEach((row) => stopDrawingRow(row));
            window.removeEventListener("keydown", spaceKeyHandler);
            document.removeEventListener(
                "click",
                closeDisplaySettingsOnOutsideClick,
            );
            stopKeepAlive();
            stopTick();
            window.removeEventListener("pointerdown", unlockAudio);
            window.removeEventListener("keydown", unlockAudio);
            confettiTimers.splice(0).forEach(clearTimeout);
        });

        return {
            appName,
            prizeOptions,
            selectedCategoryId,
            selectedPrizeIds,
            prizeCategories,
            filteredPrizes,
            selectedPrizeLabel,
            maxQuantity,
            quantity,
            platDaerah,
            settingError,
            onCategoryChange,
            saveSetting,
            form,
            bgImage,
            bgImageInitial,
            customBgImages,
            hasSetup,

            undianList,
            startDrawingRow,
            drawHandle,
            drawButtonLabel,
            drawButtonClass,
            drawButtonDisabled,
            drawButtonSize,
            drawButtonStyle,
            tableFontSize,
            resetTableFontSize,
            undianTopOffset,
            resetUndianTopOffset,
            goToRegularDraw,
            maskAlamat,

            disableTrigger,
            trigger,
            drawing,
            shuffling,
            pageSwitching,
            soundOn,
            toggleSound,
            testSound,
            tableAppearanceStyle,
            onPlateLanded,
            currentBgImage,
            customBgSizes,
            onBackgroundChange,
            onBackgroundSizeChange,
            backgroundStyle,
            showDisplaySettings,
            settingsPanel,
            toggleDisplaySettings,
        };
    },
};
</script>
