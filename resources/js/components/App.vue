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
                                        <span>Pengaturan Undian</span>
                                    </div>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-alt-danger settings-panel-close"
                                        data-toggle="layout"
                                        data-action="side_overlay_close"
                                        title="Tutup"
                                        aria-label="Tutup pengaturan undian"
                                    >
                                        <i class="fa fa-fw fa-times"></i>
                                    </button>
                                </div>
                                <div class="content-side">
                                    <div class="block">
                                        <div
                                            class="block-content block-content-full"
                                        >
                                            <!-- WILAYAH (dari .env) -->
                                            <div class="mb-3">
                                                <label
                                                    class="form-label fw-semibold"
                                                >
                                                    <i
                                                        class="fa fa-map-marked-alt me-1 text-primary"
                                                    ></i>
                                                    Wilayah Undian
                                                </label>
                                                <div
                                                    v-if="wilayah.kabkota"
                                                    class="fw-semibold fs-sm"
                                                >
                                                    {{ wilayah.kabkota }}
                                                </div>
                                                <div
                                                    v-if="wilayah.label"
                                                    class="text-muted small mt-1"
                                                >
                                                    <i
                                                        class="fa fa-map-marker-alt me-1"
                                                    ></i>
                                                    <template
                                                        v-if="wilayah.plat"
                                                        >{{ wilayah.plat }}
                                                        &mdash;
                                                    </template>
                                                    {{ wilayah.label }}
                                                </div>
                                                <div
                                                    v-if="wilayah.error"
                                                    class="text-danger small mt-1"
                                                >
                                                    <i
                                                        class="fa fa-exclamation-triangle me-1"
                                                    ></i
                                                    >{{ wilayah.error }}
                                                </div>
                                                <div
                                                    v-if="
                                                        wilayah.idLokasiMismatch
                                                            .length
                                                    "
                                                    class="text-danger small mt-1"
                                                >
                                                    <i
                                                        class="fa fa-exclamation-triangle me-1"
                                                    ></i>
                                                    Mapping wilayah memuat ID
                                                    lokasi lain:
                                                    {{
                                                        wilayah.idLokasiMismatch.join(
                                                            ", ",
                                                        )
                                                    }}. Sesuaikan APP_ID_LOKASI
                                                    di .env.
                                                </div>
                                                <div
                                                    v-if="
                                                        wilayah
                                                            .idLokasiTidakTerpakai
                                                            .length
                                                    "
                                                    class="text-warning small mt-1"
                                                >
                                                    <i
                                                        class="fa fa-info-circle me-1"
                                                    ></i>
                                                    APP_ID_LOKASI berisi ID yang
                                                    tidak dipakai mapping:
                                                    {{
                                                        wilayah.idLokasiTidakTerpakai.join(
                                                            ", ",
                                                        )
                                                    }}.
                                                </div>
                                            </div>

                                            <!-- PILIH KOORDINATOR & SAMSAT -->
                                            <div class="mb-3">
                                                <div
                                                    class="d-flex justify-content-between align-items-center"
                                                >
                                                    <label
                                                        class="form-label fw-semibold mb-0"
                                                    >
                                                        <i
                                                            class="fa fa-user-tie me-1 text-primary"
                                                        ></i>
                                                        Pilih Koordinator Lokasi
                                                    </label>
                                                    <button
                                                        v-if="
                                                            drawnSamsats.length
                                                        "
                                                        type="button"
                                                        class="btn btn-xs btn-alt-danger"
                                                        title="Hapus penanda Samsat yang sudah diundi"
                                                        @click="resetDrawn"
                                                    >
                                                        Reset Penanda
                                                    </button>
                                                </div>
                                                <div
                                                    v-if="
                                                        coordinatorKeys.length ===
                                                        0
                                                    "
                                                    class="text-muted small mt-1"
                                                >
                                                    Memuat koordinator...
                                                </div>
                                                <template v-else>
                                                    <select
                                                        class="form-select form-select-sm mt-1"
                                                        v-model="
                                                            selectedCoordinator
                                                        "
                                                        @change="
                                                            onCoordinatorChange
                                                        "
                                                    >
                                                        <option value="">
                                                            -- Pilih Koordinator
                                                            Lokasi --
                                                        </option>
                                                        <option
                                                            v-for="key in coordinatorKeys"
                                                            :key="key"
                                                            :value="key"
                                                        >
                                                            {{
                                                                coordinatorOptionLabel(
                                                                    key,
                                                                )
                                                            }}
                                                        </option>
                                                    </select>

                                                    <!-- Penanda "sudah diundi" hanya ditampilkan di
                                                         bagian dropdown koordinator agar daftar Samsat
                                                         tetap bersih dan tidak memicu error tampilan. -->
                                                    <div
                                                        v-if="
                                                            selectedCoordinatorDrawn.drawn
                                                        "
                                                        class="text-muted mt-1"
                                                        style="
                                                            font-size: 0.72rem;
                                                        "
                                                    >
                                                        <i
                                                            class="fa fa-check-circle text-success me-1"
                                                        ></i>
                                                        {{
                                                            selectedCoordinatorDrawn.drawn
                                                        }}
                                                        dari
                                                        {{
                                                            selectedCoordinatorDrawn.total
                                                        }}
                                                        Samsat di koordinator
                                                        ini sudah diundi
                                                    </div>

                                                    <div
                                                        v-if="
                                                            selectedCoordinator
                                                        "
                                                        class="border p-2 rounded mt-2"
                                                    >
                                                        <div
                                                            class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-1"
                                                        >
                                                            <span
                                                                class="fw-semibold text-primary fs-sm"
                                                            >
                                                                Daftar Samsat
                                                            </span>
                                                            <button
                                                                type="button"
                                                                class="btn btn-xs btn-alt-secondary"
                                                                @click="
                                                                    toggleSelectAllSamsat
                                                                "
                                                            >
                                                                {{
                                                                    isAllSamsatSelected
                                                                        ? "Deselect All"
                                                                        : "Select All"
                                                                }}
                                                            </button>
                                                        </div>
                                                        <div
                                                            v-for="samsat in selectedSamsatOptions"
                                                            :key="samsat"
                                                            class="form-check mb-1"
                                                        >
                                                            <input
                                                                class="form-check-input"
                                                                type="checkbox"
                                                                :id="
                                                                    'samsat-' +
                                                                    samsat
                                                                "
                                                                :value="samsat"
                                                                v-model="
                                                                    selectedSamsats
                                                                "
                                                            />
                                                            <label
                                                                class="form-check-label fs-xs"
                                                                :for="
                                                                    'samsat-' +
                                                                    samsat
                                                                "
                                                            >
                                                                {{ samsat }}
                                                            </label>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>

                                            <!-- KATEGORI HADIAH -->
                                            <div class="mb-3">
                                                <label
                                                    class="form-label fw-semibold"
                                                >
                                                    <i
                                                        class="fa fa-tags me-1 text-primary"
                                                    ></i>
                                                    Kategori Hadiah
                                                </label>
                                                <select
                                                    class="form-select form-select-sm"
                                                    v-model="selectedCategoryId"
                                                    @change="onCategoryChange"
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

                                            <!-- PILIH HADIAH -->
                                            <div class="mb-3">
                                                <label
                                                    class="form-label fw-semibold"
                                                >
                                                    <i
                                                        class="fa fa-gift me-1 text-primary"
                                                    ></i>
                                                    Pilih Hadiah
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
                                                    class="border p-2 rounded"
                                                    style="
                                                        max-height: 200px;
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

                                            <!-- JUMLAH PEMENANG PER SAMSAT -->
                                            <div class="mb-3">
                                                <label
                                                    class="form-label fw-semibold"
                                                >
                                                    <i
                                                        class="fa fa-users me-1 text-primary"
                                                    ></i>
                                                    Jumlah Pemenang per Samsat
                                                </label>
                                                <input
                                                    type="number"
                                                    min="1"
                                                    class="form-control form-control-sm"
                                                    v-model.number="
                                                        winnersPerPage
                                                    "
                                                />
                                                <div
                                                    class="text-muted mt-1"
                                                    style="font-size: 0.72rem"
                                                >
                                                    {{ selectedSamsats.length }}
                                                    Samsat ×
                                                    {{ winnersPerPage || 0 }} =
                                                    <strong>{{
                                                        requiredWinners
                                                    }}</strong>
                                                    pemenang / halaman
                                                </div>
                                            </div>

                                            <!-- SUMMARY -->
                                            <div class="mb-3 settings-summary">
                                                <div
                                                    class="d-flex justify-content-between small mb-1"
                                                >
                                                    <span>Samsat dipilih:</span>
                                                    <strong>{{
                                                        selectedSamsats.length
                                                    }}</strong>
                                                </div>
                                                <div
                                                    class="d-flex justify-content-between small mb-1"
                                                >
                                                    <span
                                                        >Pemenang
                                                        dibutuhkan:</span
                                                    >
                                                    <strong
                                                        :class="
                                                            requiredWinners > 0
                                                                ? 'text-primary'
                                                                : 'text-muted'
                                                        "
                                                    >
                                                        {{ requiredWinners }}
                                                        orang
                                                    </strong>
                                                </div>
                                                <div
                                                    class="d-flex justify-content-between small mb-1"
                                                >
                                                    <span
                                                        >Total stok
                                                        hadiah:</span
                                                    >
                                                    <strong
                                                        :class="
                                                            totalSelectedStock >=
                                                                requiredWinners &&
                                                            requiredWinners > 0
                                                                ? 'text-success'
                                                                : 'text-danger'
                                                        "
                                                    >
                                                        {{ totalSelectedStock }}
                                                        unit
                                                    </strong>
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

                                            <!-- TOMBOL SIMPAN & SIGN-IN -->
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <button
                                                        class="btn btn-alt-primary w-100"
                                                        v-on:click="saveSetting"
                                                        :disabled="
                                                            !!settingError ||
                                                            requiredWinners ===
                                                                0
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
                                        class="fa fa-expand-arrows-alt me-1 text-primary"
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
                                            class="fa fa-arrows-up-down me-1 text-primary"
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
                                            class="fa fa-table me-1 text-primary"
                                        ></i>
                                        Tabel Undian
                                    </label>
                                    <button
                                        type="button"
                                        class="btn btn-xs btn-alt-secondary"
                                        @click="resetTableAppearance"
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
                                        min="70"
                                        max="180"
                                        step="5"
                                        v-model.number="tableSize"
                                    />
                                    <input
                                        type="number"
                                        class="form-control form-control-sm"
                                        style="width: 84px"
                                        min="70"
                                        max="180"
                                        step="5"
                                        v-model.number="tableSize"
                                    />
                                </div>
                                <div
                                    class="text-muted mt-1"
                                    style="font-size: 0.72rem"
                                >
                                    Skala tabel (ukuran sel): {{ tableSize }}%
                                </div>

                                <div class="mt-2">
                                    <label class="form-label fs-xs mb-0"
                                        >Ukuran Font Tabel</label
                                    >
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

                                <div class="row g-2 mt-1">
                                    <div class="col-6">
                                        <label class="form-label fs-xs mb-0"
                                            >Latar Kartu</label
                                        >
                                        <input
                                            type="color"
                                            class="form-control form-control-sm p-1"
                                            style="height: 34px"
                                            v-model="tableCardBg"
                                        />
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fs-xs mb-0"
                                            >Garis Tabel</label
                                        >
                                        <input
                                            type="color"
                                            class="form-control form-control-sm p-1"
                                            style="height: 34px"
                                            v-model="tableBorderColor"
                                        />
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fs-xs mb-0"
                                            >Judul Kecamatan</label
                                        >
                                        <input
                                            type="color"
                                            class="form-control form-control-sm p-1"
                                            style="height: 34px"
                                            v-model="tableTitleColor"
                                        />
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fs-xs mb-0"
                                            >Latar Header</label
                                        >
                                        <input
                                            type="color"
                                            class="form-control form-control-sm p-1"
                                            style="height: 34px"
                                            v-model="tableHeaderBg"
                                        />
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fs-xs mb-0"
                                            >Teks Header</label
                                        >
                                        <input
                                            type="color"
                                            class="form-control form-control-sm p-1"
                                            style="height: 34px"
                                            v-model="tableHeaderColor"
                                        />
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fs-xs mb-0"
                                            >Latar Isi Tabel</label
                                        >
                                        <input
                                            type="color"
                                            class="form-control form-control-sm p-1"
                                            style="height: 34px"
                                            v-model="tableBodyBg"
                                        />
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fs-xs mb-0"
                                            >Teks Isi Tabel</label
                                        >
                                        <input
                                            type="color"
                                            class="form-control form-control-sm p-1"
                                            style="height: 34px"
                                            v-model="tableTextColor"
                                        />
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fs-xs mb-0"
                                            >Teks Hadiah</label
                                        >
                                        <input
                                            type="color"
                                            class="form-control form-control-sm p-1"
                                            style="height: 34px"
                                            v-model="tablePrizeColor"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- SOUND EFFECT: on/off + test -->
                            <div class="mb-3">
                                <div
                                    class="d-flex justify-content-between align-items-center"
                                >
                                    <label
                                        class="form-label fw-semibold mb-0"
                                    >
                                        <i
                                            class="fa fa-volume-up me-1 text-primary"
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

                            <!-- BACKGROUND POSTER & HALAMAN UNDIAN -->
                            <BackgroundSetting
                                storage-key="undianBackgroundImage"
                                variant="undian"
                                poster-storage-key="undianBackgroundPosterImage"
                                accent="primary"
                                :preview-style="tableAppearanceStyle"
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
                        class="btn btn-sm btn-alt-success me-1 page-nav-btn"
                        title="Grand Prize"
                        :disabled="pageSwitching"
                        @click="goToGrandPrize"
                    >
                        <span
                            v-if="pageSwitching"
                            class="page-nav-spinner"
                            aria-hidden="true"
                        ></span>
                        <i v-else class="fa fa-fw fa-gift"></i>
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
                        class="btn btn-sm btn-alt-primary ms-1 page-nav-btn"
                        title="Pengaturan Tampilan"
                        data-settings-toggle
                        @click="toggleDisplaySettings"
                    >
                        <i class="fa fa-fw fa-sliders-h"></i>
                    </button>
                </div>
            </div>
        </header>
        <main
            id="main-container"
            style="padding-top: unset !important"
            @keydown.space.prevent="handleSpaceBar"
        >
            <div
                class="bg-undian"
                @click="trigger"
                tabindex="0"
                :style="backgroundStyle"
            >
                <div class="bg-black-50">
                    <!-- POSTER UTAMA (pertama kali): hanya menampilkan background poster -->
                    <div v-if="!hasSetup" class="poster-blank"></div>

                    <!-- TAMPILAN UNDIAN -->
                    <div v-else class="content container-fluid undian-content">
                        <div
                            class="undian-center"
                            :style="[
                                { marginTop: undianTopOffset + 'px' },
                                tableAppearanceStyle,
                            ]"
                        >
                            <!-- TAMPILAN UNDIAN & HASIL: kartu ala papan
                                 undian dengan piringan nopol per karakter -->
                            <div
                                class="kec-grid"
                                :class="{
                                    'kec-grid-single':
                                        form.samsats.length <= 1,
                                    spinning: gridSpinning,
                                }"
                                :style="{ '--kec-cols': kecColumns }"
                            >
                                <article
                                    v-for="(samsat, si) in form.samsats"
                                    :key="samsat"
                                    class="kec-item"
                                >
                                    <div class="kec-card on">
                                        <h2 class="kec-head">
                                            <span
                                                class="kec-title"
                                                :title="samsat"
                                                >{{ samsat }}</span
                                            >
                                            <span
                                                class="cnt"
                                                :title="prizeTitle"
                                                >{{ prizeTitle }}</span
                                            >
                                        </h2>
                                        <div class="th">
                                            <span>No</span>
                                            <span>Nopol</span>
                                            <span>Nama</span>
                                        </div>
                                        <ol>
                                            <li
                                                v-for="(
                                                    item, row
                                                ) in rowsBySamsat[
                                                    samsat
                                                ] || []"
                                                :key="row"
                                            >
                                                <template v-if="item">
                                                    <span class="n">{{
                                                        String(
                                                            row + 1,
                                                        ).padStart(2, "0")
                                                    }}</span>
                                                    <span class="t pl">
                                                        <PlateReel
                                                            :prefix="
                                                                item.kodeWilayah
                                                            "
                                                            :digits="
                                                                item.angka
                                                            "
                                                            :suffix="
                                                                item.kodeSeri
                                                            "
                                                            :spinning="
                                                                item.spinning
                                                            "
                                                            :settle-delay="
                                                                settleDelayFor(
                                                                    si,
                                                                    row,
                                                                )
                                                            "
                                                            @landed="
                                                                onRowLanded(
                                                                    item,
                                                                )
                                                            "
                                                        />
                                                    </span>
                                                    <span
                                                        class="t nm"
                                                        :class="{
                                                            land: item.revealed,
                                                        }"
                                                    >
                                                        {{
                                                            item.revealed
                                                                ? item.nama ||
                                                                  "-"
                                                                : item.spinning
                                                                  ? item.spinName
                                                                  : "XXXXXXXX"
                                                        }}
                                                    </span>
                                                </template>
                                                <template v-else>
                                                    <span class="n">{{
                                                        String(
                                                            row + 1,
                                                        ).padStart(2, "0")
                                                    }}</span>
                                                    <span class="t"></span>
                                                    <span class="t"></span>
                                                </template>
                                            </li>
                                        </ol>
                                    </div>
                                </article>
                            </div>
                            <div
                                v-if="form.samsats.length === 0"
                                class="text-center text-muted py-3"
                            >
                                Belum ada Samsat yang dipilih.
                            </div>

                            <!-- Draw Button & Hint -->
                            <div
                                class="text-center draw-button-wrap"
                                v-if="form.samsats.length > 0"
                            >
                                <button
                                    type="button"
                                    class="btn btn-rounded btn-xl fw-semibold"
                                    id="btnDraw"
                                    :class="
                                        shuffling
                                            ? 'btn-warning'
                                            : drawing
                                              ? 'btn-danger'
                                              : 'btn-success'
                                    "
                                    :style="drawButtonStyle"
                                    @click="drawHandle"
                                    v-bind:disabled="
                                        shuffling ||
                                        (!drawing && disableTrigger)
                                    "
                                    autofocus
                                >
                                    {{
                                        shuffling
                                            ? "Sedang Mengacak"
                                            : drawing
                                              ? "Stop Pengundian!"
                                              : "Start Pengundian!"
                                    }}
                                </button>
                            </div>
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

.bg-black-op-50 {
    background-color: rgba(0, 0, 0, 0.75) !important;
}

.bg-black-50 {
    min-height: 100vh;
    background-color: transparent !important;
}

.undian-content {
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 140px);
}

/* Form undian: jarak dari atas diatur lewat pengaturan (undianTopOffset) */
.undian-center {
    margin-bottom: auto;
    width: 100%;
}

/* Daftar kartu: baris terakhir otomatis di tengah.
   Jumlah kolom diatur lewat --kec-cols (lihat kecColumns):
   - 4 kartu  -> 2 + 2
   - 5 kartu  -> 3 + 2 (2 kartu bawah di tengah)
   - 6 kartu  -> 3 + 3
*/
.kec-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: stretch;
    gap: 1rem;
    width: 100%;
}

.kec-item {
    flex: 0 0 100%;
    min-width: 0;
}

@media (min-width: 768px) {
    .kec-item {
        flex: 0 0
            calc((100% - (var(--kec-cols, 2) - 1) * 1rem) / var(--kec-cols, 2));
        min-width: 0;
    }

    /* Bila hanya 1 kecamatan, tampilkan satu kartu di tengah */
    .kec-grid.kec-grid-single .kec-item {
        flex-basis: min(100%, 640px);
    }
}

/* Kartu pemenang: gaya ala papan undian (semua ukuran memakai em agar
   mengikuti --kec-card-fs yang diatur lewat panel Pengaturan Tampilan) */
.kec-card {
    --acc: #2563eb;
    /* Grid kolom tabel (No | Nopol | Nama) didefinisikan di sini supaya
       header .th (font 0.62em) dan baris li memakai ukuran kolom yang
       SAMA PERSIS. Em di variabel ini dihitung dari font kartu — bukan
       dari font tiap elemen — sehingga header selalu sejajar dengan isi
       barisnya, termasuk saat ukuran font tabel diubah. */
    --kec-cols: 1.6em 7.8em 1fr;
    --kec-col-gap: 0.6em;
    --kec-col-pad: 0.2em;
    position: relative;
    overflow: hidden;
    background: linear-gradient(
        180deg,
        var(--kec-card-bg, #ffffff),
        color-mix(in srgb, var(--kec-card-bg, #ffffff) 90%, #62758f)
    );
    border: 1px solid
        color-mix(in srgb, var(--kec-border, #dde6f2) 85%, transparent);
    border-radius: 1.2em;
    padding: 1.4em 1.4em 1em;
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.65),
        0 1.3em 2.4em -1.3em rgba(15, 40, 90, 0.22);
    color: var(--kec-td-color, #1f2937);
    font-family: "Plus Jakarta Sans", system-ui, -apple-system, "Segoe UI",
        Roboto, sans-serif;
    font-size: var(--kec-card-fs, 1rem);
    width: 100%;
    min-width: 0;
    transition:
        border-color 0.5s,
        box-shadow 0.5s;
}

.kec-card.on {
    border-color: color-mix(
        in srgb,
        var(--acc) 55%,
        var(--kec-border, #dde6f2)
    );
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.65),
        0 1.3em 2.4em -1.3em rgba(15, 40, 90, 0.22),
        0 0 0 0.2em color-mix(in srgb, var(--acc) 18%, transparent);
}

/* Jarak tombol start dari kartu pengundian */
.draw-button-wrap {
    margin-top: 2.25rem;
    margin-bottom: 1.5rem;
}

/* Kepala kartu: nama Samsat + badge hadiah (mengikuti referensi) */
.kec-head {
    margin: 0 0 1em;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.6em;
    font-size: 1.25em;
    font-weight: 800;
    letter-spacing: -0.015em;
    /* Line-height longgar agar huruf dengan descender (y, j, p, g)
       tidak tersayat saat teks disangga overflow hidden. */
    line-height: 1.35;
    min-width: 0;
}

.kec-title {
    flex: 1 1 auto;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.35;
    color: var(--kec-title-color, var(--acc));
}

.cnt {
    flex: 0 0 auto;
    /* Badge pill judul hadiah: border pil + nowrap seperti referensi. */
    border: 1px solid var(--kec-border, #dde6f2);
    border-radius: 99em;
    padding: 0.2em 0.65em;
    white-space: nowrap;
    font-size: 0.72em;
    font-weight: 600;
    color: var(--kec-prize-color, #62758f);
}

/* Header tabel & baris pemenang memakai grid yang sama.
   Lebar kolom & jarak diambil dari variabel .kec-card agar header
   (font 0.62em) dan baris (font penuh) selalu sejajar. */
.kec-card .th,
.kec-card li {
    display: grid;
    grid-template-columns: var(--kec-cols);
    align-items: center;
    gap: var(--kec-col-gap);
    padding: 0 var(--kec-col-pad);
}

.kec-card .th {
    font-size: 0.62em;
    font-weight: 700;
    color: var(--kec-th-color, #62758f);
    text-transform: uppercase;
    letter-spacing: 0.14em;
    padding-top: 0.35em;
    padding-bottom: 0.5em;
    background: color-mix(in srgb, var(--kec-th-bg, #eef2ff) 55%, transparent);
    border-bottom: 1px solid
        color-mix(in srgb, var(--kec-border, #dde6f2) 90%, transparent);
    border-radius: 0.4em 0.4em 0 0;
}

.kec-card .th span:nth-child(2) {
    text-align: center;
}

.kec-card ol {
    list-style: none;
    margin: 0;
    padding: 0;
}

.kec-card li {
    position: relative;
    height: 3.05em;
    border-bottom: 1px solid
        color-mix(in srgb, var(--kec-border, #dde6f2) 70%, transparent);
}

.kec-card li:last-child {
    border-bottom: 0;
}

.n {
    color: color-mix(in srgb, var(--kec-td-color, #1f2937) 55%, transparent);
    font-size: 0.8em;
    font-weight: 600;
    letter-spacing: 0.04em;
    font-variant-numeric: tabular-nums;
}

.t {
    min-width: 0;
    opacity: 0;
    transform: translateY(0.3em);
    transition:
        opacity 0.4s ease,
        transform 0.4s ease;
}

.pl {
    display: flex;
    justify-content: center;
    white-space: nowrap;
}

.nm {
    font-size: 0.95em;
    font-weight: 600;
    line-height: 1.25;
    overflow-wrap: anywhere;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    color: var(--kec-td-color, #1f2937);
}

/* Baris yang belum terisi: tampilkan bar abu (skeleton) */
.kec-card li::after {
    content: "";
    position: absolute;
    left: 2.3em;
    right: 24%;
    top: 50%;
    height: 0.6em;
    margin-top: -0.3em;
    border-radius: 0.4em;
    background: color-mix(in srgb, var(--kec-border, #dde6f2) 60%, #ffffff);
    transition: opacity 0.3s;
}

.kec-card.on .t {
    opacity: 1;
    transform: none;
}

.kec-card.on li::after {
    opacity: 0;
}

/* Nama yang masih mengacak: blur seperti efek mesin slot */
.kec-grid.spinning .nm:not(.land) {
    filter: blur(0.7px);
    opacity: 0.7;
}

.nm.land {
    animation: nm-in 0.3s ease;
}

@keyframes nm-in {
    from {
        opacity: 0.2;
        transform: translateX(-0.4em);
    }
}

@media (prefers-reduced-motion: reduce) {
    .t,
    .kec-card {
        transition: none;
    }

    .t {
        transform: none;
    }

    .nm.land {
        animation: none;
    }
}

#btnDraw {
    margin-top: 0;
    border: 0 !important;
    border-radius: 999px !important;
    letter-spacing: 0.03em;
    background: #2563eb !important;
    color: #fff !important;
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.22);
    transition:
        transform 0.18s ease,
        box-shadow 0.18s ease,
        filter 0.18s ease;
    will-change: transform;
}

/* Tombol Stop: merah seperti halaman referensi */
#btnDraw.btn-danger {
    background: #d9453d !important;
    box-shadow: 0 14px 30px rgba(217, 69, 61, 0.3);
}

/* Tombol saat menunggu data dari server */
#btnDraw.btn-warning {
    background: #f59e0b !important;
}

#btnDraw:hover:not(:disabled),
#btnDraw:focus-visible {
    transform: translateY(-2px);
    box-shadow: 0 20px 38px rgba(15, 23, 42, 0.26);
    filter: brightness(1.03);
}

#btnDraw:active:not(:disabled) {
    transform: translateY(0);
    box-shadow: 0 8px 18px rgba(15, 23, 42, 0.2);
}

#btnDraw:disabled {
    box-shadow: none;
    opacity: 0.6;
}

/* Poster utama: hanya background (poster) */
.poster-blank {
    min-height: 100vh;
    width: 100%;
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

/* Sidebar Pengaturan Undian: header tetap di atas, konten yang bergulir */
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

#side-overlay .settings-summary {
    background-color: #f8f9fa !important;
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
        const shuffling = ref(false); // 3s lock when starting
        const settling = ref(false); // hasil mendarat satu per satu setelah Stop
        // Penanda hasil undian berhasil dipulihkan dari server setelah
        // koneksi terputus (recoverDraw). Bila true, hasil langsung
        // ditampilkan tanpa menunggu klik Stop.
        const wasRecovered = ref(false);
        const requestPending = ref(false);
        const winnersDetermined = ref(false);
        const pageSwitching = ref(false);
        const drawButtonSize = ref(
            Number(localStorage.getItem("regularDrawButtonSize")) || 100,
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

        // Batas waktu pengundian. Tujuannya agar tombol "Stop Pengundian!"
        // tidak pernah terkunci permanen bila request ke server lambat/menggantung.
        // Batas dibuat besar karena request pertama setelah perangkat standby
        // bisa sangat lambat (koneksi database/jaringan baru terbangun).
        const DRAW_START_DELAY = 3000;
        const DRAW_REQUEST_TIMEOUT = 45000;
        // Watchdog memberi ruang ekstra untuk pemulihan hasil (recoverDraw)
        // setelah request gagal/terputus.
        const DRAW_WATCHDOG_TIMEOUT = DRAW_REQUEST_TIMEOUT + 20000;

        // Kecepatan animasi reveal hasil:
        // - ROW_STAGGER_MS  : jeda antar baris saat berhenti bertahap.
        // - CHAR_STAGGER_MS : jeda antar karakter nopol saat mendarat.
        const ROW_STAGGER_MS = 45;
        const CHAR_STAGGER_MS = 45;

        const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

        const updateDisableTrigger = () => {
            disableTrigger.value =
                shuffling.value ||
                requestPending.value ||
                winnersDetermined.value;
        };

        // Lepas kunci "Sedang Mengacak" apa pun kondisi request-nya.
        const unlockShuffle = () => {
            shuffling.value = false;
            updateDisableTrigger();
        };

        // ===== Sound effect (meniru halaman referensi, Web Audio API) =====
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
            [[1, 1], [2.01, 0.4], [2.76, 0.3], [5.4, 0.12]].forEach(
                ([r, a]) => {
                    const o = AC.createOscillator();
                    const g = AC.createGain();
                    o.type = "sine";
                    o.frequency.value = f * r;
                    g.gain.setValueAtTime(0.0001, t);
                    g.gain.linearRampToValueAtTime(
                        (v || 0.06) * a,
                        t + 0.005,
                    );
                    g.gain.exponentialRampToValueAtTime(
                        0.0001,
                        t + d / r ** 0.4,
                    );
                    o.connect(g);
                    g.connect(AC.destination);
                    o.start(t);
                    o.stop(t + d + 0.05);
                },
            );
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

        // Penanda sesi undian: hasil request dari sesi lama tidak boleh
        // menimpa state sesi terbaru.
        let drawSession = 0;

        // --- Wilayah, koordinator & pengaturan state ---
        const wilayah = reactive({
            kabkota: inject("kabkota") || "",
            plat: "",
            label: "",
            samsat: [],
            kecamatan: [],
            idLokasi: [],
            idLokasiMismatch: [],
            idLokasiTidakTerpakai: [],
            winnersPerPage: Number(inject("winnersPerPage")) || 5,
            error: "",
        });
        const coordinatorMap = ref({});
        const selectedCoordinator = ref("");
        const selectedSamsats = ref([]);
        const selectedCategoryId = ref("");
        const selectedPrizeIds = ref([]);
        const prizeOptions = ref([]);
        const winnersPerPage = ref(wilayah.winnersPerPage || 5);

        const coordinatorKeys = computed(() =>
            Object.keys(coordinatorMap.value),
        );

        const coordinatorLabel = (key) => {
            return coordinatorMap.value[key]?.label || key;
        };

        const selectedSamsatOptions = computed(() => {
            return (
                coordinatorMap.value[selectedCoordinator.value]?.samsat || []
            );
        });

        // Pilih koordinator: seluruh Samsat di bawahnya otomatis dipilih,
        // sama seperti alur pengundian undianbapenda.
        const onCoordinatorChange = () => {
            winnersDetermined.value = false;
            updateDisableTrigger();

            if (
                selectedCoordinator.value &&
                coordinatorMap.value[selectedCoordinator.value]
            ) {
                selectedSamsats.value = [...selectedSamsatOptions.value];
            } else {
                selectedSamsats.value = [];
            }
        };

        const isAllSamsatSelected = computed(() => {
            const currentSamsats = selectedSamsatOptions.value;

            if (currentSamsats.length === 0) return false;

            return currentSamsats.every((samsat) =>
                selectedSamsats.value.includes(samsat),
            );
        });

        const toggleSelectAllSamsat = () => {
            const currentSamsats = selectedSamsatOptions.value;

            if (currentSamsats.length === 0) return;

            if (isAllSamsatSelected.value) {
                selectedSamsats.value = selectedSamsats.value.filter(
                    (s) => !currentSamsats.includes(s),
                );
            } else {
                const newSelection = [...selectedSamsats.value];
                currentSamsats.forEach((s) => {
                    if (!newSelection.includes(s)) {
                        newSelection.push(s);
                    }
                });
                selectedSamsats.value = newSelection;
            }
        };

        // Samsat yang sudah diundi disimpan di localStorage per wilayah.
        // Nilai selalu dinormalisasi menjadi array string agar data lama/rusak
        // tidak membuat halaman error saat penanda dibaca.
        const drawnSamsats = ref([]);
        const drawnStorageKey = () =>
            `drawnSamsat:${wilayah.kabkota || "default"}`;

        const normalizeDrawn = (value) => {
            if (!Array.isArray(value)) return [];
            return value.filter(
                (item) => typeof item === "string" && item !== "",
            );
        };

        const persistDrawn = () => {
            try {
                localStorage.setItem(
                    drawnStorageKey(),
                    JSON.stringify(drawnSamsats.value),
                );
            } catch (e) {
                /* abaikan */
            }
        };

        const loadDrawn = () => {
            try {
                const raw = localStorage.getItem(drawnStorageKey());
                drawnSamsats.value = raw ? normalizeDrawn(JSON.parse(raw)) : [];
            } catch (e) {
                drawnSamsats.value = [];
            }
        };

        const markDrawn = (samsats) => {
            if (!Array.isArray(samsats) || samsats.length === 0) return;

            const set = new Set(drawnSamsats.value);
            samsats.forEach((samsat) => {
                if (typeof samsat === "string" && samsat !== "")
                    set.add(samsat);
            });
            drawnSamsats.value = Array.from(set);
            persistDrawn();
        };

        const resetDrawn = () => {
            drawnSamsats.value = [];
            try {
                localStorage.removeItem(drawnStorageKey());
            } catch (e) {
                /* abaikan */
            }
        };

        // Statistik penanda per koordinator. Penanda hanya ditampilkan pada
        // dropdown koordinator, bukan lagi di tiap baris Samsat.
        const drawnSamsatSet = computed(() => new Set(drawnSamsats.value));

        const coordinatorDrawnStats = (key) => {
            const samsats = Array.isArray(coordinatorMap.value[key]?.samsat)
                ? coordinatorMap.value[key].samsat
                : [];
            const drawn = samsats.reduce(
                (total, samsat) =>
                    total + (drawnSamsatSet.value.has(samsat) ? 1 : 0),
                0,
            );

            return {
                total: samsats.length,
                drawn,
                complete: samsats.length > 0 && drawn === samsats.length,
            };
        };

        const coordinatorOptionLabel = (key) => {
            const label = coordinatorLabel(key);
            const stats = coordinatorDrawnStats(key);

            if (stats.drawn === 0) return label;
            if (stats.complete) return `✓ Semua Samsat sudah diundi — ${label}`;

            return `✓ ${stats.drawn}/${stats.total} Samsat sudah diundi — ${label}`;
        };

        const selectedCoordinatorDrawn = computed(() =>
            selectedCoordinator.value
                ? coordinatorDrawnStats(selectedCoordinator.value)
                : { total: 0, drawn: 0, complete: false },
        );

        // Kategori hadiah (mirip Grand Prize), urut dari data API
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

        const extractGram = (name) => {
            const cleaned = String(name || "").replace(",", ".");
            const match = cleaned.match(/([0-9]+(?:\.[0-9]+)?)/);
            return match ? parseFloat(match[1]) : 0;
        };

        const filteredPrizes = computed(() => {
            if (!selectedCategoryId.value) return [];
            return prizeOptions.value
                .filter(
                    (item) =>
                        item.prize_category?.id === selectedCategoryId.value,
                )
                .sort((a, b) => extractGram(b.name) - extractGram(a.name));
        });

        const onCategoryChange = () => {
            if (!selectedCategoryId.value) {
                selectedPrizeIds.value = [];
                return;
            }
            // Pilih semua hadiah dalam kategori sebagai default (bisa di-uncheck manual).
            selectedPrizeIds.value = filteredPrizes.value.map(
                (item) => item.id,
            );
        };

        // Background dinamis: poster (sebelum setup) & halaman undian punya
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
            if (hasSetup.value) return customBgImages.page || bgImage;
            return customBgImages.poster || bgImageInitial;
        });

        const currentBgSize = computed(() =>
            hasSetup.value ? customBgSizes.page : customBgSizes.poster,
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
            localStorage.setItem("regularDrawButtonSize", String(normalized));
        });

        // Jarak undian dari atas (px), tersimpan permanen di localStorage.
        const DEFAULT_UNDIAN_TOP = 24;
        const storedUndianTop = localStorage.getItem("regularUndianTopOffset");
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
            localStorage.setItem("regularUndianTopOffset", String(normalized));
        });

        const resetUndianTopOffset = () => {
            undianTopOffset.value = DEFAULT_UNDIAN_TOP;
            localStorage.setItem(
                "regularUndianTopOffset",
                String(DEFAULT_UNDIAN_TOP),
            );
        };

        // Tampilan tabel undian (ukuran & warna), tersimpan permanen di localStorage.
        const TABLE_DEFAULTS = {
            size: 100,
            fontSize: 14,
            cardBg: "#ffffff",
            borderColor: "#e5e7eb",
            titleColor: "#0d6efd",
            headerBg: "#eef2ff",
            headerColor: "#3730a3",
            bodyBg: "#ffffff",
            textColor: "#1f2937",
            prizeColor: "#0d6efd",
        };

        const readStoredValue = (key, fallback) => {
            const value = localStorage.getItem(key);
            return value !== null && value !== "" ? value : fallback;
        };

        const tableSize = ref(
            Number(readStoredValue("undianTableSize", TABLE_DEFAULTS.size)) ||
                TABLE_DEFAULTS.size,
        );
        const tableFontSize = ref(
            Number(
                readStoredValue("undianTableFontSize", TABLE_DEFAULTS.fontSize),
            ) || TABLE_DEFAULTS.fontSize,
        );
        const tableCardBg = ref(
            readStoredValue("undianTableCardBg", TABLE_DEFAULTS.cardBg),
        );
        const tableBorderColor = ref(
            readStoredValue(
                "undianTableBorderColor",
                TABLE_DEFAULTS.borderColor,
            ),
        );
        const tableTitleColor = ref(
            readStoredValue("undianTableTitleColor", TABLE_DEFAULTS.titleColor),
        );
        const tableHeaderBg = ref(
            readStoredValue("undianTableHeaderBg", TABLE_DEFAULTS.headerBg),
        );
        const tableHeaderColor = ref(
            readStoredValue(
                "undianTableHeaderColor",
                TABLE_DEFAULTS.headerColor,
            ),
        );
        const tableBodyBg = ref(
            readStoredValue("undianTableBodyBg", TABLE_DEFAULTS.bodyBg),
        );
        const tableTextColor = ref(
            readStoredValue("undianTableTextColor", TABLE_DEFAULTS.textColor),
        );
        const tablePrizeColor = ref(
            readStoredValue("undianTablePrizeColor", TABLE_DEFAULTS.prizeColor),
        );

        watch(tableSize, (value) => {
            const normalized = Math.min(
                Math.max(Number(value) || 100, 70),
                180,
            );
            if (normalized !== value) {
                tableSize.value = normalized;
                return;
            }
            localStorage.setItem("undianTableSize", String(normalized));
        });

        watch(tableFontSize, (value) => {
            const normalized = Math.min(
                Math.max(Number(value) || TABLE_DEFAULTS.fontSize, 8),
                28,
            );
            if (normalized !== value) {
                tableFontSize.value = normalized;
                return;
            }
            localStorage.setItem("undianTableFontSize", String(normalized));
        });

        [
            [tableCardBg, "undianTableCardBg"],
            [tableBorderColor, "undianTableBorderColor"],
            [tableTitleColor, "undianTableTitleColor"],
            [tableHeaderBg, "undianTableHeaderBg"],
            [tableHeaderColor, "undianTableHeaderColor"],
            [tableBodyBg, "undianTableBodyBg"],
            [tableTextColor, "undianTableTextColor"],
            [tablePrizeColor, "undianTablePrizeColor"],
        ].forEach(([refValue, key]) => {
            watch(refValue, (value) => localStorage.setItem(key, value));
        });

        const tableAppearanceStyle = computed(() => {
            const scale =
                Math.min(Math.max(Number(tableSize.value) || 100, 70), 180) /
                100;
            const fontSize = Math.min(
                Math.max(
                    Number(tableFontSize.value) || TABLE_DEFAULTS.fontSize,
                    8,
                ),
                28,
            );
            // Kartu memakai satuan em; nama pemenang memakai 0.95em, jadi
            // font kartu = fontSize / 0.95 agar setting "Ukuran Font Tabel"
            // tetap sesuai dengan ukuran teks nama.
            const cardFs = (fontSize / 0.95) * scale;

            return {
                "--kec-card-fs": `${cardFs.toFixed(2)}px`,
                "--kec-card-bg": tableCardBg.value,
                "--kec-border": tableBorderColor.value,
                "--kec-title-color": tableTitleColor.value,
                "--kec-th-bg": tableHeaderBg.value,
                "--kec-th-color": tableHeaderColor.value,
                "--kec-td-bg": tableBodyBg.value,
                "--kec-td-color": tableTextColor.value,
                "--kec-prize-color": tablePrizeColor.value,
            };
        });

        const resetTableAppearance = () => {
            tableSize.value = TABLE_DEFAULTS.size;
            tableFontSize.value = TABLE_DEFAULTS.fontSize;
            tableCardBg.value = TABLE_DEFAULTS.cardBg;
            tableBorderColor.value = TABLE_DEFAULTS.borderColor;
            tableTitleColor.value = TABLE_DEFAULTS.titleColor;
            tableHeaderBg.value = TABLE_DEFAULTS.headerBg;
            tableHeaderColor.value = TABLE_DEFAULTS.headerColor;
            tableBodyBg.value = TABLE_DEFAULTS.bodyBg;
            tableTextColor.value = TABLE_DEFAULTS.textColor;
            tablePrizeColor.value = TABLE_DEFAULTS.prizeColor;

            localStorage.setItem(
                "undianTableSize",
                String(TABLE_DEFAULTS.size),
            );
            localStorage.setItem(
                "undianTableFontSize",
                String(TABLE_DEFAULTS.fontSize),
            );
            localStorage.setItem("undianTableCardBg", TABLE_DEFAULTS.cardBg);
            localStorage.setItem(
                "undianTableBorderColor",
                TABLE_DEFAULTS.borderColor,
            );
            localStorage.setItem(
                "undianTableTitleColor",
                TABLE_DEFAULTS.titleColor,
            );
            localStorage.setItem(
                "undianTableHeaderBg",
                TABLE_DEFAULTS.headerBg,
            );
            localStorage.setItem(
                "undianTableHeaderColor",
                TABLE_DEFAULTS.headerColor,
            );
            localStorage.setItem("undianTableBodyBg", TABLE_DEFAULTS.bodyBg);
            localStorage.setItem(
                "undianTableTextColor",
                TABLE_DEFAULTS.textColor,
            );
            localStorage.setItem(
                "undianTablePrizeColor",
                TABLE_DEFAULTS.prizeColor,
            );
        };

        // Form yang di-commit saat klik Simpan
        const form = reactive({
            samsats: [],
            prize_ids: [],
            quantity: 0,
            winnersPerPage: 5,
        });

        const hasSetup = computed(() => {
            return form.samsats.length > 0 && form.prize_ids.length > 0;
        });

        // Satu judul hadiah untuk seluruh tabel pada halaman undian.
        // Gunakan deskripsi hadiah (bukan nama hadiah).
        const prizeTitle = computed(() => {
            const descriptions = form.prize_ids
                .map((id) => prizeOptions.value.find((p) => p.id === id))
                .map((prize) => (prize?.description || "").trim())
                .filter(Boolean);

            return descriptions.join(", ") || "-";
        });

        const requiredWinners = computed(() => {
            return (
                selectedSamsats.value.length *
                (Number(winnersPerPage.value) || 0)
            );
        });

        const totalSelectedStock = computed(() => {
            return selectedPrizeIds.value.reduce((sum, id) => {
                const prize = prizeOptions.value.find((p) => p.id === id);
                return sum + (prize ? prize.available_quantity : 0);
            }, 0);
        });

        const settingError = computed(() => {
            if (wilayah.error) return wilayah.error;
            if (wilayah.idLokasiMismatch.length) {
                return `APP_ID_LOKASI tidak cocok dengan mapping wilayah (ID lain: ${wilayah.idLokasiMismatch.join(", ")}). Periksa .env.`;
            }
            if (coordinatorKeys.value.length === 0)
                return "Daftar koordinator belum tersedia. Periksa APP_KABKOTA di .env.";
            if (!selectedCoordinator.value) return "Pilih koordinator lokasi.";
            if (selectedSamsats.value.length === 0)
                return "Pilih minimal 1 Samsat.";
            if (!selectedCategoryId.value) return "Pilih kategori hadiah.";
            if (selectedPrizeIds.value.length === 0)
                return "Pilih minimal 1 hadiah.";
            if (!winnersPerPage.value || Number(winnersPerPage.value) < 1) {
                return "Jumlah pemenang per Samsat minimal 1.";
            }
            if (totalSelectedStock.value < requiredWinners.value) {
                return `Stok hadiah (${totalSelectedStock.value}) kurang dari pemenang yang dibutuhkan (${requiredWinners.value}).`;
            }
            return "";
        });

        const undianList = ref([]);
        const winnerList = ref([]);

        // Grid "spinning": nama yang belum mendarat tampil blur.
        const gridSpinning = computed(() => drawing.value || settling.value);

        // Delay mendarat per baris: baris berikutnya berhenti ROW_STAGGER_MS
        // setelah baris sebelumnya (meniru halaman referensi).
        const settleDelayFor = (samsatIndex, row) =>
            (samsatIndex * (Number(form.winnersPerPage) || 1) + row) *
            ROW_STAGGER_MS;

        // Selalu kembalikan tepat winnersPerPage baris per Samsat, baik saat
        // belum diundi, sedang mengacak, maupun setelah pemenang ditampilkan.
        const rowsBySamsat = computed(() => {
            const per = Number(form.winnersPerPage) || 0;
            const map = {};

            form.samsats.forEach((samsat, index) => {
                map[samsat] = Array.from({ length: per }, (_, row) => {
                    return undianList.value[index * per + row] || null;
                });
            });

            return map;
        });

        // Jumlah kolom kartu agar tersusun seimbang (± 2 baris):
        // 4 kartu -> 2 kolom (2+2), 5 -> 3 kolom (3+2), 6 -> 3 kolom (3+3), dst.
        const kecColumns = computed(() => {
            const n = form.samsats.length;
            if (n <= 2) return Math.max(n, 1);
            return Math.min(Math.ceil(n / 2), 4);
        });

        const initUndianList = () => {
            undianList.value = [];
            const per = Number(form.winnersPerPage) || 0;
            const total = form.samsats.length * per;
            for (let i = 0; i < total; i++) {
                undianList.value.push({
                    id: i + 1,
                    // Placeholder mengikuti halaman referensi: pola piringan
                    // "XX 0000 XXX" dan nama "XXXXXXXX" sebelum diundi.
                    kodeWilayah: "XX",
                    angka: "0000",
                    kodeSeri: "XXX",
                    spinning: false,
                    revealed: false,
                    spinName: "",
                    id_kendaraan: "",
                    nama: "Belum teracak",
                    alamat: "Belum teracak",
                    roda: "-",
                    lokasi: form.samsats[Math.floor(i / per)] || "",
                    prize: { id: null, name: "Belum teracak" },
                });
            }
        };

        // Nama acak untuk tampilan selama mengacak: kombinasi nama depan
        // (+ tengah) + belakang orang Indonesia yang umum, sehingga terlihat
        // seperti nama asli — bukan huruf/suku kata acak.
        const NAMA_DEPAN = [
            "AGUS", "AHMAD", "ANDI", "ANTO", "ARIS", "BAMBANG", "BAYU",
            "BUDI", "DARMAWATI", "DEWI", "DIMAS", "DUDI", "DWI", "EGI",
            "EKO", "ENDANG", "FAJAR", "FARIDA", "FITRI", "GALIH", "GUNTUR",
            "HADI", "HENDRA", "HERI", "IDA", "INDAH", "IRWAN", "JOKO",
            "KARTIKA", "LATIF", "LESTARI", "LILIS", "MARNI", "MARYANI",
            "MUHAMMAD", "NANIK", "NUR", "OKTA", "PUTRI", "RAHMA", "RATNA",
            "RINA", "RINI", "RUDY", "SARI", "SITI", "SLAMET", "SRI",
            "SUCI", "SUDARNO", "SUGIYONO", "SUKARNO", "SULISTYOWATI",
            "SUMARNO", "SUNARTO", "SUPARNO", "SUSETYO", "SUTARSIH",
            "SUWANDI", "TAUFIK", "TEGUH", "TRI", "UMI", "WAHYU", "WARTI",
            "YATI", "YULIANI", "YUNI", "ZAINAL",
        ];
        const NAMA_BELAKANG = [
            "ADI", "ANTONI", "ARDI", "ASIH", "BASUKI", "CAHYONO",
            "DARMAWAN", "FEBRIANTO", "HANDOKO", "HARYANTO", "HIDAYAT",
            "HUTABARAT", "ISWANTO", "KURNIAWAN", "KUSUMA", "LAKSANA",
            "LUTFIANA", "MAULANA", "NUGRAHA", "NUGROHO", "PALUPI",
            "PAMUNGKAS", "PRASETYO", "PRATAMA", "PUJIASTUTI", "PURWANTO",
            "RAHARDJO", "RAHAYU", "RAHMAT", "SAPUTRA", "SARSWATI",
            "SETIAWAN", "SIHOMBING", "SIMBOLON", "SITOMPUL", "SUGIYANTO",
            "SUHARTO", "SUMARNI", "SUPRAPTI", "SURATMAN", "SURYANA",
            "SUSANTI", "SUSILOWATI", "SUTARNO", "SUWARTI", "TANJUNG",
            "WAHYUNI", "WIBOWO", "WIDAYATI", "WIDODO", "WIJAYA",
            "WINARTO", "YULIASTUTI", "YUSUF",
        ];

        const pick = (list) => list[Math.floor(Math.random() * list.length)];

        const randomName = () => {
            // Nama lengkap: depan [+ tengah, 40% kemungkinan] + belakang.
            const parts = [pick(NAMA_DEPAN)];
            if (Math.random() < 0.4) parts.push(pick(NAMA_DEPAN));
            parts.push(pick(NAMA_BELAKANG));
            return parts.join(" ");
        };

        // Ticker global untuk nama acak. Aman terhadap penggantian objek
        // baris (undianList) saat hasil pemenang diterima dari server.
        let nameTick = null;

        // Pemulihan hasil undian yang sedang berjalan (shared promise).
        let recoveryInFlight = null;

        const spinTick = () => {
            // Bunyi "tek-tek" mesin slot, sinkron dengan pergantian nama.
            tone(900 + Math.random() * 500, 0.03, "triangle", 0.014);
            undianList.value.forEach((row) => {
                if (row.spinning && !row.revealed) {
                    row.spinName = randomName();
                }
            });
        };

        // Ticker nama acak; tick pertama langsung dibunyikan agar suara
        // terdengar tepat saat tombol Start ditekan.
        const startNameTick = () => {
            if (nameTick) return;
            // Tick pertama langsung dimainkan (bukan menunggu interval).
            tone(900 + Math.random() * 500, 0.03, "triangle", 0.014);
            nameTick = setInterval(spinTick, 70);
            spinTick();
        };

        const stopNameTick = () => {
            if (!nameTick) return;
            clearInterval(nameTick);
            nameTick = null;
        };

        const startDrawingRow = (row) => {
            row.spinning = true;
            row.revealed = false;
            row.spinName = randomName();
        };

        const stopDrawingRow = (row) => {
            row.spinning = false;
        };

        // Samakan penulisan nama Samsat agar pencocokan tidak sensitif huruf
        // dan toleran label gabungan ("Blora -> Cepu", "â†’" rusak, "Kab. X").
        const normalizeSamsatLabel = (value) =>
            String(value || "")
                .replace(/â†’|→/g, "->")
                .replace(/\b(Kab\.|Kota)\b/g, "")
                .replace(/\s+/g, " ")
                .trim()
                .toUpperCase();

        /**
         * Urutkan hasil undian sesuai kelompok Samsat pada form.samsats.
         *
         * Wajib untuk jalur pemulihan (recoverDraw): urutan data dari
         * /api/draw/recent tidak dijamin sama dengan urutan kelompok
         * form.samsats, sedangkan tampilan memetakan baris berdasarkan
         * posisi. Tanpa pengurutan ini hasil pulihan tampil tercampur
         * antar Samsat.
         */
        const orderWinnersForDisplay = (items) => {
            if (!Array.isArray(items) || items.length === 0) return items;
            if (!form.samsats.length) return items;

            const groups = form.samsats.map((samsat) =>
                normalizeSamsatLabel(samsat)
                    .split("->")
                    .flatMap((part) => part.split(","))
                    .map((part) => part.trim())
                    .filter(Boolean),
            );

            const ordered = [];
            const leftover = [];

            items.forEach((item) => {
                const lokasi = normalizeSamsatLabel(item.lokasi);
                const groupIndex = groups.findIndex((names) =>
                    names.includes(lokasi),
                );

                if (groupIndex >= 0) {
                    if (!ordered[groupIndex]) ordered[groupIndex] = [];
                    ordered[groupIndex].push(item);
                } else {
                    leftover.push(item);
                }
            });

            const flat = [];
            for (let i = 0; i < groups.length; i++) {
                if (ordered[i]) flat.push(...ordered[i]);
            }

            return [...flat, ...leftover];
        };

        /**
         * Coba pulihkan hasil undian yang mungkin sudah tersimpan di server.
         *
         * Bila request pengundian terputus (mis. perangkat standby lama),
         * backend bisa saja sudah menyelesaikan dan menyimpan pemenang,
         * tetapi responsnya tidak sampai ke front-end. Endpoint
         * /api/draw/recent mengembalikan hasil undian yang baru saja
         * tersimpan agar tampilan tetap konsisten dengan database.
         *
         * Konteks pengundian (quantity, prize_id, samsats) dikirim agar
         * server hanya mengembalikan draw yang cocok — bukan undian lama
         * yang kebetulan baru tersimpan. Percobaan diulang beberapa kali
         * karena server bisa saja masih memproses draw saat percobaan
         * pertama (SP lambat setelah standby).
         */
        const recoverDraw = async (session = null) => {
            const params = {
                is_global: 0,
                quantity: form.quantity,
                prize_id: form.prize_ids[0] ?? "",
                samsats: form.samsats,
            };

            const runRecovery = async () => {
                for (let attempt = 0; attempt < 3; attempt++) {
                    if (attempt > 0) {
                        await delay(2500);
                    }

                    // Bila operator sudah memulai sesi undian baru, pemulihan
                    // sesi lama tidak boleh lagi menerapkan hasil.
                    if (session !== null && session !== drawSession) {
                        return false;
                    }

                    try {
                        const response = await axios.get("api/draw/recent", {
                            timeout: 10000,
                            params,
                        });
                        const items = response.data?.data;

                        if (
                            response.data?.recovered &&
                            Array.isArray(items) &&
                            items.length > 0
                        ) {
                            if (session !== null && session !== drawSession) {
                                return false;
                            }

                            winnerList.value = items;
                            wasRecovered.value = true;
                            getOptionsPrize();
                            alert(
                                "Koneksi ke server sempat terputus, tetapi hasil undian sudah tersimpan di server dan kini ditampilkan.",
                            );
                            return true;
                        }
                    } catch (error) {
                        console.error("Gagal memulihkan hasil undian:", error);
                    }
                }

                return false;
            };

            // Pemulihan dibagi (shared) antar pemanggil: pickWinner dan
            // drawHandle bisa sama-sama memanggil recoverDraw. Tanpa ini dua
            // loop pemulihan berjalan paralel (alert ganda, hasil ganda).
            if (recoveryInFlight) {
                return recoveryInFlight;
            }

            recoveryInFlight = runRecovery();
            const result = await recoveryInFlight;
            recoveryInFlight = null;

            return result;
        };

        const pickWinner = async (session) => {
            requestPending.value = true;
            updateDisableTrigger();

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
                        samsats: form.samsats,
                        per_samsat: form.winnersPerPage,
                    },
                    {
                        signal: controller.signal,
                        timeout: DRAW_REQUEST_TIMEOUT,
                    },
                );

                // Abaikan hasil dari sesi undian yang sudah tidak berlaku.
                if (session !== drawSession) return false;

                winnerList.value = Array.isArray(response.data?.data)
                    ? response.data.data
                    : [];
                getOptionsPrize();
                return true;
            } catch (error) {
                console.error("Gagal update:", error.response?.data || error);

                if (session !== drawSession) return false;

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
                    const recovered = await recoverDraw(session);

                    if (recovered) {
                        return true;
                    }
                }

                undianList.value.forEach((row) => stopDrawingRow(row));

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

                if (session === drawSession) {
                    requestPending.value = false;
                    updateDisableTrigger();
                }
            }
        };

        const parseWinner = () => {
            undianList.value = orderWinnersForDisplay(winnerList.value).map(
                (item, index) => {
                    const parts = item.no_polisi.match(
                        /[a-zA-Z]+|[0-9]+/g,
                    ) || [
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
                        spinning: true,
                        revealed: false,
                        spinName: randomName(),
                        id_kendaraan: item.id_kendaraan,
                        nama: item.nama,
                        alamat: item.alamat,
                        roda: item.roda,
                        lokasi: item.lokasi,
                        prize: item.prize || { name: "-" },
                    };
                },
            );
        };

        const settleTimers = [];
        // Penanda confetti agar hanya meledak satu kali per sesi undian.
        let confettiFired = false;
        // Penanda musik penutup agar fanfare hanya bunyi sekali per undian,
        // di jalur mana pun (utama maupun fallback).
        let chimeFired = false;

        const playEndChime = () => {
            if (chimeFired) return;
            chimeFired = true;
            chime();
        };

        const clearSettleTimers = () => {
            settleTimers.splice(0).forEach(clearTimeout);
        };

        // Confetti meledak selama 2 detik dengan arah yang berbeda-beda:
        // bergantian dari kiri, kanan, tengah bawah, kedua sudut bawah, dan
        // hujan dari tengah atas.
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
                settleTimers.push(
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

        // Dipanggil PlateReel setiap satu baris selesai mendarat. Confetti
        // baru ditembakkan saat baris TERAKHIR tampil: 300ms setelahnya,
        // tepat ketika animasi masuknya nama selesai.
        const onRowLanded = (item) => {
            // Abaikan event pendaratan di luar alur settle (mis. saat
            // undian gagal dan piringan placeholder berhenti).
            if (!settling.value) return;

            item.revealed = true;

            // Nada pendek bernada naik mengikuti urutan baris (referensi).
            const g = undianList.value.indexOf(item);
            if (g >= 0) tone(520 + g * 14, 0.08, "triangle", 0.05);

            if (
                !confettiFired &&
                undianList.value.length > 0 &&
                undianList.value.every((row) => row.revealed)
            ) {
                confettiFired = true;
                settleTimers.push(
                    setTimeout(() => {
                        fireConfetti();
                        playEndChime();
                        // 600ms: memberi ruang animasi kilat karakter
                        // terakhir & animasi masuk nama selesai tuntas.
                    }, 600),
                );
            }
        };

        // Selesaikan hasil secara bertahap: baris berikutnya berhenti
        // ROW_STAGGER_MS setelah baris sebelumnya, lalu tiap karakter nopol
        // mendarat satu per satu (di dalam PlateReel) dan nama tampil
        // setelahnya.
        const settleWinners = () => {
            const rows = undianList.value;

            rows.forEach((row, g) => {
                settleTimers.push(
                    setTimeout(() => {
                        row.spinning = false;
                    }, g * ROW_STAGGER_MS),
                );
            });

            // Kebersihan akhir (fallback): paksa semua baris tampil dan
            // hentikan ticker nama. Confetti & musik penutup di sini hanya
            // jaring pengaman bila ada event pendaratan yang terlewat.
            const total =
                rows.length * ROW_STAGGER_MS +
                11 * CHAR_STAGGER_MS +
                1200;
            settleTimers.push(
                setTimeout(() => {
                    rows.forEach((row) => {
                        row.spinning = false;
                        row.revealed = true;
                    });
                    stopNameTick();
                    settling.value = false;

                    if (!confettiFired) {
                        confettiFired = true;
                        fireConfetti();
                        playEndChime();
                    }
                }, total),
            );
        };

        // Tampilkan hasil undian: data diparsing, animasi berhenti bertahap,
        // Samsat ditandai sudah diundi. Confetti meledak di akhir settle.
        const finishDraw = () => {
            // Benturan tombol stop + bunyi klik (persis referensi).
            tone(140, 0.2, "sine", 0.14, 50);
            tone(1700, 0.04, "square", 0.02);

            settling.value = true;
            parseWinner();
            settleWinners();
            markDrawn(form.samsats);
            // Setelah diundi, Samsat tersebut otomatis tidak terpilih lagi
            // di sidebar agar undian berikutnya tidak mengulang Samsat yang sama.
            selectedSamsats.value = selectedSamsats.value.filter(
                (samsat) => !form.samsats.includes(samsat),
            );
            drawing.value = false;
            winnersDetermined.value = true;
            updateDisableTrigger();
        };

        const drawHandle = async () => {
            // Saat pengundian berjalan tombol Stop harus selalu bisa diklik,
            // jadi kunci hanya berlaku sebelum pengundian dimulai.
            if (disableTrigger.value && !drawing.value) return;

            if (!drawing.value) {
                // Sesi baru: pastikan penanda hasil undian sebelumnya tidak
                // ikut mengunci tombol pada pengundian berikutnya.
                drawSession += 1;
                const session = drawSession;

                drawing.value = true;
                winnersDetermined.value = false;
                shuffling.value = true;
                settling.value = false;
                wasRecovered.value = false;
                confettiFired = false;
                chimeFired = false;
                updateDisableTrigger();
                clearSettleTimers();
                undianList.value.forEach((row) => startDrawingRow(row));
                startNameTick();
                audioCtx();

                // Pengaman terakhir: bila segala mekanisme pemulihan gagal,
                // tombol tidak boleh selamanya terkunci di "Sedang Mengacak".
                setTimeout(() => {
                    if (session === drawSession && shuffling.value) {
                        unlockShuffle();
                    }
                }, DRAW_WATCHDOG_TIMEOUT + 5000);

                const startedAt = Date.now();
                let picked = false;

                try {
                    // Watchdog memastikan alur selalu selesai walau request
                    // macet, sehingga tombol Stop tidak terkunci selamanya.
                    const result = await Promise.race([
                        pickWinner(session),
                        delay(DRAW_WATCHDOG_TIMEOUT).then(() => false),
                    ]);
                    picked = result === true;
                } catch (error) {
                    console.error("Gagal memulai pengundian:", error);
                    picked = false;
                }

                // Tombol baru berubah jadi "Stop Pengundian!" SETELAH data
                // dari server selesai diterima dan animasi pengacakan telah
                // berjalan minimal DRAW_START_DELAY.
                const elapsed = Date.now() - startedAt;
                if (elapsed < DRAW_START_DELAY) {
                    await delay(DRAW_START_DELAY - elapsed);
                }

                if (session !== drawSession) return;

                if (!picked) {
                    // Upaya terakhir sebelum menyerah: server bisa saja
                    // selesai menyimpan pemenang setelah request dianggap
                    // gagal/timeout (SP lambat setelah standby). Tanpa ini,
                    // data yang tersimpan di server tidak pernah tampil.
                    const recovered = await recoverDraw(session);
                    picked = recovered && session === drawSession;
                }

                if (!picked) {
                    // Undian gagal / melewati batas waktu: hentikan animasi,
                    // buka kunci tombol, dan biarkan pengguna mencoba lagi.
                    clearSettleTimers();
                    stopNameTick();
                    undianList.value.forEach((row) => stopDrawingRow(row));
                    drawing.value = false;
                    winnersDetermined.value = false;
                    requestPending.value = false;
                    // Abaikan respons yang datang terlambat dari sesi ini.
                    drawSession += 1;
                }

                unlockShuffle();

                // Hasil pulihan (koneksi sempat terputus): data sudah ada di
                // server, langsung ditampilkan tanpa menunggu klik Stop —
                // sesuai janji pada notifikasi pemulihan.
                if (picked && wasRecovered.value) {
                    wasRecovered.value = false;
                    finishDraw();
                }
                // keep drawing true until user stops
            } else {
                // Data pemenang belum siap -> jangan proses hasil kosong.
                if (requestPending.value) return;

                // Data kosong dari server: hentikan animasi dan beri tahu
                // operator, jangan menampilkan tabel kosong + confetti.
                if (!winnerList.value.length) {
                    stopNameTick();
                    undianList.value.forEach((row) => stopDrawingRow(row));
                    drawing.value = false;
                    winnersDetermined.value = false;
                    updateDisableTrigger();
                    alert(
                        "Data pemenang tidak diterima dari server. Silakan coba lagi.",
                    );
                    return;
                }

                finishDraw();
            }
        };

        const saveSetting = () => {
            if (drawing.value || shuffling.value || settling.value) {
                alert(
                    "Hentikan pengundian terlebih dahulu sebelum menyimpan pengaturan.",
                );
                return;
            }

            if (settingError.value) {
                alert(settingError.value);
                return;
            }

            form.samsats = [...selectedSamsats.value];
            form.prize_ids = [...selectedPrizeIds.value];
            form.winnersPerPage = Number(winnersPerPage.value) || 1;
            form.quantity = form.samsats.length * form.winnersPerPage;

            const el = document.getElementById("page-container");
            if (el) {
                el.classList.remove("side-overlay-o");
            }

            // Siap untuk pengundian baru: buka kunci dari hasil undian sebelumnya.
            winnersDetermined.value = false;
            requestPending.value = false;
            drawSession += 1; // batalkan respons lama yang mungkin masih berjalan
            initUndianList();
            updateDisableTrigger();
        };

        const trigger = () => {
            // document.getElementById('btnDraw').focus();
        };

        const goToGrandPrize = () => {
            if (pageSwitching.value) return;
            pageSwitching.value = true;
            window.location.href = "/grand-prize";
        };

        const handleSpaceBar = (event) => {
            if (event.code === "Space" || event.key === " ") {
                event.preventDefault();
                if (disableTrigger.value) return;
                drawHandle(drawing.value);
            }
        };

        const getOptionsPrize = async () => {
            try {
                const response = await axios.get("/api/get-data/prize");
                prizeOptions.value = response.data;

                // Kategori default: "Hadiah Lainnya" bila tersedia, dipilih
                // otomatis saat daftar hadiah pertama kali dimuat.
                if (!selectedCategoryId.value) {
                    const def = prizeCategories.value.find((c) =>
                        /lainnya/i.test(String(c.name || "")),
                    );
                    if (def) {
                        selectedCategoryId.value = def.id;
                        onCategoryChange();
                    }
                }
            } catch (error) {
                console.error("Gagal memuat data hadiah:", error);
            }
        };

        const getWilayah = async () => {
            try {
                const response = await axios.get("/api/get-data/wilayah");
                wilayah.kabkota = response.data.kabkota || wilayah.kabkota;
                wilayah.plat = response.data.plat || "";
                wilayah.label = response.data.label || "";

                // Kode plat daerah aktif untuk animasi pengundian.
                const platKode = response.data?.plat_kode;
                if (Array.isArray(platKode) && platKode.length) {
                    platDaerah.value = platKode;
                }

                wilayah.samsat = response.data.samsat || [];
                wilayah.kecamatan = response.data.kecamatan || [];
                wilayah.idLokasi = response.data.id_lokasi || [];
                wilayah.idLokasiMismatch =
                    response.data.id_lokasi_mismatch || [];
                wilayah.idLokasiTidakTerpakai =
                    response.data.id_lokasi_tidak_terpakai || [];
                wilayah.error = response.data.error || "";

                loadDrawn();

                if (response.data.winners_per_page) {
                    wilayah.winnersPerPage = Number(
                        response.data.winners_per_page,
                    );
                    if (!form.prize_ids.length) {
                        winnersPerPage.value = wilayah.winnersPerPage;
                    }
                }
            } catch (error) {
                wilayah.error = "Gagal memuat data wilayah.";
                console.error("Gagal memuat data wilayah:", error);
            }
        };

        /**
         * Mapping koordinator (Plat) -> daftar Samsat untuk halaman undian
         * biasa, mengikuti alur undianbapenda.
         */
        const getCoordinatorMap = async () => {
            try {
                const response = await axios.get("/api/get-data/coordinator");
                coordinatorMap.value = response.data || {};

                // Bila wilayah hanya punya satu koordinator, langsung pilih
                // agar operator tidak perlu memilih ulang.
                const keys = Object.keys(coordinatorMap.value);
                if (keys.length === 1) {
                    selectedCoordinator.value = keys[0];
                    onCoordinatorChange();
                }
            } catch (error) {
                console.error("Gagal memuat data koordinator:", error);
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
            getCoordinatorMap();
            startKeepAlive();
        });

        onUnmounted(() => {
            document.removeEventListener(
                "click",
                closeDisplaySettingsOnOutsideClick,
            );
            stopKeepAlive();
            stopNameTick();
            clearSettleTimers();
            window.removeEventListener("pointerdown", unlockAudio);
            window.removeEventListener("keydown", unlockAudio);
        });

        return {
            appName,
            prizeOptions,
            prizeCategories,
            filteredPrizes,
            selectedCategoryId,
            selectedPrizeIds,
            onCategoryChange,
            platJateng,
            platDaerah,
            wilayah,
            coordinatorMap,
            coordinatorKeys,
            selectedCoordinator,
            selectedSamsats,
            selectedSamsatOptions,
            coordinatorLabel,
            onCoordinatorChange,
            isAllSamsatSelected,
            toggleSelectAllSamsat,
            drawnSamsats,
            resetDrawn,
            coordinatorOptionLabel,
            selectedCoordinatorDrawn,
            winnersPerPage,
            requiredWinners,
            totalSelectedStock,
            settingError,
            saveSetting,
            form,
            hasSetup,
            undianList,
            rowsBySamsat,
            prizeTitle,
            kecColumns,
            gridSpinning,
            settleDelayFor,
            onRowLanded,
            startDrawingRow,
            stopDrawingRow,
            drawHandle,
            handleSpaceBar,
            goToGrandPrize,

            disableTrigger,
            trigger,
            drawing,
            shuffling,
            settling,
            pageSwitching,
            soundOn,
            toggleSound,
            testSound,
            currentBgImage,
            customBgImages,
            customBgSizes,
            onBackgroundChange,
            onBackgroundSizeChange,
            backgroundStyle,
            showDisplaySettings,
            settingsPanel,
            toggleDisplaySettings,
            bgImage,
            bgImageInitial,
            drawButtonSize,
            drawButtonStyle,
            undianTopOffset,
            resetUndianTopOffset,
            tableSize,
            tableFontSize,
            tableCardBg,
            tableBorderColor,
            tableTitleColor,
            tableHeaderBg,
            tableHeaderColor,
            tableBodyBg,
            tableTextColor,
            tablePrizeColor,
            tableAppearanceStyle,
            resetTableAppearance,
        };
    },
};
</script>
