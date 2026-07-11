<div>
    <x-admin-light.topbar title="Membres" />

    <div class="mx-auto max-w-[1280px] px-8 py-8">
        <div class="mb-5">
            <h2 class="mb-1 text-[17px] font-bold text-brand">Base de données des membres</h2>
            <div class="h-[3px] w-9 rounded bg-accent"></div>
            <p class="mt-2 text-xs text-[#9AA6B8]">{{ $total }} compte{{ $total > 1 ? 's' : '' }} — classés par rôle</p>
        </div>

        <div class="mb-6 flex flex-wrap items-center gap-2 rounded-[14px] border border-brand/10 bg-white p-3 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
            <input wire:model.live.debounce.400ms="recherche" type="search" placeholder="Rechercher nom ou email…" class="w-52 rounded-full border border-brand/15 px-4 py-2 text-xs outline-none focus:border-azure" />
            <span class="mx-1 h-5 w-px bg-brand/10"></span>
            @foreach (['tous' => 'Tous les rôles', 'admin' => 'Administrateurs', 'mentor' => 'Mentors', 'member' => 'Membres'] as $value => $label)
                <button wire:click="setFiltreRole('{{ $value }}')" class="rounded-full border px-3 py-1.5 text-xs font-semibold {{ $filtreRole === $value ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">{{ $label }}</button>
            @endforeach
            <span class="mx-1 h-5 w-px bg-brand/10"></span>
            @foreach (['tous' => 'Tous', 'actifs' => 'Actifs', 'suspendus' => 'Suspendus'] as $value => $label)
                <button wire:click="setFiltreStatut('{{ $value }}')" class="rounded-full border px-3 py-1.5 text-xs font-semibold {{ $filtreStatut === $value ? 'border-brand bg-brand text-white' : 'border-brand/10 bg-white text-[#5B677A]' }}">{{ $label }}</button>
            @endforeach
            <span class="mx-1 h-5 w-px bg-brand/10"></span>
            <select wire:model.live="periode" class="rounded-full border border-brand/15 px-3 py-1.5 text-xs font-semibold text-[#5B677A] outline-none">
                <option value="toutes">Toute période</option>
                <option value="30j">Inscrits — 30 derniers jours</option>
                <option value="90j">Inscrits — 3 derniers mois</option>
                <option value="annee">Inscrits — cette année</option>
            </select>
            <a href="{{ route('admin.inscription') }}" wire:navigate class="ml-auto rounded-full bg-accent px-4 py-1.5 text-xs font-bold text-white hover:bg-accent-600">+ Nouvelle inscription</a>
        </div>

        <div class="space-y-7">
            @forelse ($groupes as $titre => $liste)
                <div>
                    <p class="mb-3 text-xs font-bold uppercase tracking-widest text-[#9AA6B8]">{{ $titre }} ({{ $liste->count() }})</p>
                    <div class="rounded-[18px] border border-brand/10 bg-white px-5 shadow-[0_2px_8px_rgba(3,29,89,.05)]">
                        @foreach ($liste as $m)
                            <div class="border-t border-[#EDF0F5] py-3.5 first:border-t-0">
                                <div class="flex flex-wrap items-center gap-4 {{ $m['actif'] ? '' : 'opacity-55' }}">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white" style="background: linear-gradient(135deg, {{ $m['role'] === 'admin' ? '#AC0100, #D95B5A' : ($m['role'] === 'mentor' ? '#F5A623, #F7C873' : '#031D59, #4F6FBF') }})">{{ $m['initiales'] }}</span>
                                    <div class="min-w-[200px] flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-[13.5px] font-bold text-brand">{{ $m['nom'] }}</p>
                                            @if ($m['restreint'])
                                                <span class="rounded-full bg-[#F5A623]/15 px-2 py-0.5 text-[9.5px] font-bold text-[#B87A0D]">ACCÈS LIMITÉ</span>
                                            @endif
                                            @unless ($m['actif'])
                                                <span class="rounded-full bg-[#EEF1F5] px-2 py-0.5 text-[9.5px] font-bold text-[#9AA6B8]">SUSPENDU</span>
                                            @endunless
                                        </div>
                                        <p class="text-xs text-[#5B677A]">{{ $m['email'] }} · {{ $m['telephone'] }}@if ($m['ville']) · {{ $m['ville'] }}@endif</p>
                                        <p class="mt-0.5 text-[11px] text-[#9AA6B8]">Inscrit le {{ $m['depuis'] }}</p>
                                    </div>
                                    <button wire:click="toggleStatut({{ $m['id'] }})" class="shrink-0 rounded-[9px] border border-[#C9D3E6] px-3 py-1.5 text-xs font-bold text-brand hover:bg-cloud">{{ $m['actif'] ? 'Suspendre' : 'Réactiver' }}</button>
                                    <div class="flex shrink-0 items-center gap-1">
                                        <button wire:click="voir({{ $m['id'] }})" title="Voir le dossier complet" class="rounded-lg p-1.5 {{ $voirId === $m['id'] ? 'bg-brand/10 text-brand' : 'text-[#9AA6B8] hover:bg-brand/10 hover:text-brand' }}">
                                            <x-ui.icon name="eye" class="size-4" />
                                        </button>
                                        <button wire:click="openEdit({{ $m['id'] }})" title="Modifier" class="rounded-lg p-1.5 {{ $editingId === $m['id'] ? 'bg-brand/10 text-brand' : 'text-[#9AA6B8] hover:bg-brand/10 hover:text-brand' }}">
                                            <x-ui.icon name="pencil" class="size-4" />
                                        </button>
                                        <button wire:click="qr({{ $m['id'] }})" title="Carte membre & QR code" class="rounded-lg p-1.5 {{ $qrId === $m['id'] ? 'bg-brand/10 text-brand' : 'text-[#9AA6B8] hover:bg-brand/10 hover:text-brand' }}">
                                            <x-ui.icon name="qr-code" class="size-4" />
                                        </button>
                                        @if ($m['role'] !== 'admin')
                                            <button wire:click="delete({{ $m['id'] }})" wire:confirm="Supprimer définitivement le compte de {{ $m['nom'] }} ? Cette action est irréversible." title="Supprimer" class="rounded-lg p-1.5 text-[#9AA6B8] hover:bg-accent/10 hover:text-accent">
                                                <x-ui.icon name="trash-2" class="size-4" />
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                {{-- Dossier complet --}}
                                @if ($voirId === $m['id'] && $dossier !== [])
                                    @php $d = $dossier['member']; $app = $dossier['application']; @endphp
                                    <div class="mt-3 rounded-xl bg-[#F8FAFC] p-4">
                                        <p class="mb-2.5 text-[11.5px] font-bold uppercase tracking-[0.05em] text-brand">Profil</p>
                                        <div class="grid grid-cols-1 gap-x-6 gap-y-2.5 sm:grid-cols-3">
                                            @foreach ([
                                                'Référence' => $d['reference'], 'Code carte' => $d['code'], 'Email' => $d['email'],
                                                'Téléphone' => $d['telephone'], 'Genre' => $d['genre'], 'Ville' => $d['ville'],
                                                'Paroisse' => $d['paroisse'], 'Secteur' => $d['secteur'], 'Profil' => $d['profil'],
                                                'Organisation' => $d['organisation'], 'Date de naissance' => $d['date_naissance'], "Date d'adhésion" => $d['date_adhesion'],
                                            ] as $label => $value)
                                                @if (! empty($value))
                                                    <div>
                                                        <p class="text-[11px] font-bold text-[#5B677A]">{{ $label }}</p>
                                                        <p class="mt-0.5 text-[12.5px] text-ink">{{ $value }}</p>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                        @if ($d['bio'])
                                            <p class="mt-3 text-[12.5px] italic text-[#5B677A]">« {{ $d['bio'] }} »</p>
                                        @endif

                                        @if (! empty($dossier['formations']))
                                            <p class="mb-2 mt-4 text-[11.5px] font-bold uppercase tracking-[0.05em] text-brand">Formations</p>
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($dossier['formations'] as $f)
                                                    <span class="rounded-full px-3 py-1 text-[11px] font-semibold {{ $f['completed'] ? 'bg-[#22A85A]/10 text-[#22A85A]' : 'bg-azure/10 text-azure' }}">{{ $f['title'] }} · {{ $f['progress'] }}%{{ $f['completed'] ? ' ✓' : '' }}</span>
                                                @endforeach
                                            </div>
                                        @endif

                                        @if ($app)
                                            <p class="mb-2.5 mt-4 text-[11.5px] font-bold uppercase tracking-[0.05em] text-brand">Formulaire d'adhésion</p>
                                            <div class="grid grid-cols-1 gap-x-6 gap-y-2.5 sm:grid-cols-3">
                                                @foreach ([
                                                    'Sexe' => $app['sexe'] ?? null, "Tranche d'âge" => $app['tranche_age'] ?? null, 'WhatsApp' => $app['whatsapp'] ?? null,
                                                    'Diocèse' => $app['diocese'] ?? null, 'Paroisse' => $app['paroisse'] ?? null,
                                                    'Statut actuel' => implode(', ', $app['statut_actuel'] ?? []), "Niveau d'études" => $app['niveau_etudes'] ?? null,
                                                    'Domaines de formation' => $app['domaines_formation'] ?? null, 'Compétences' => implode(', ', $app['competences'] ?? []),
                                                    'A une activité ?' => $app['a_activite'] ?? null, 'Activité' => $app['nom_activite'] ?? null,
                                                    'Attentes' => implode(', ', $app['attentes'] ?? []), 'Formations souhaitées' => implode(', ', $app['formations_interet'] ?? []),
                                                    'Défi principal' => $app['defi_principal'] ?? null, 'Revenu mensuel' => $app['revenu_mensuel'] ?? null,
                                                ] as $label => $value)
                                                    @if (! empty($value))
                                                        <div>
                                                            <p class="text-[11px] font-bold text-[#5B677A]">{{ $label }}</p>
                                                            <p class="mt-0.5 text-[12.5px] text-ink">{{ $value }}</p>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                {{-- Modification --}}
                                @if ($editingId === $m['id'])
                                    <div class="mt-3 grid grid-cols-1 gap-3.5 rounded-xl border border-brand/10 bg-[#F8FAFC] p-4 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Prénom</label>
                                            <input wire:model="prenom" type="text" class="w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure" />
                                            @error('prenom') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Nom</label>
                                            <input wire:model="nom" type="text" class="w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure" />
                                            @error('nom') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Téléphone</label>
                                            <input wire:model="telephone" type="text" class="w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure" />
                                            @error('telephone') <span class="text-xs text-accent">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Ville</label>
                                            <input wire:model="ville" type="text" class="w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure" />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-semibold text-[#5B677A]">Rôle</label>
                                            <select wire:model.live="role" class="w-full rounded-[9px] border border-brand/15 bg-white px-3 py-2 text-sm outline-none focus:border-azure">
                                                <option value="member">Membre</option>
                                                <option value="mentor">Mentor</option>
                                                <option value="admin">Administrateur</option>
                                            </select>
                                        </div>
                                        @if ($role === 'admin')
                                            <div class="rounded-[10px] border border-brand/10 bg-white p-3 sm:col-span-2">
                                                <label class="inline-flex items-center gap-2 text-xs font-bold text-brand">
                                                    <input wire:model.live="accesComplet" type="checkbox" class="size-4 rounded border-brand/20 text-brand" /> Accès complet à toute l'administration
                                                </label>
                                                @if (! $accesComplet)
                                                    <div class="mt-2.5 grid grid-cols-2 gap-1.5 sm:grid-cols-3">
                                                        @foreach ($sectionsDisponibles as $slug => $label)
                                                            <label class="inline-flex items-center gap-2 text-xs text-[#5B677A]">
                                                                <input wire:model="sections" type="checkbox" value="{{ $slug }}" class="size-3.5 rounded border-brand/20 text-brand" /> {{ $label }}
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                        <div class="flex gap-2 sm:col-span-2">
                                            <button wire:click="saveEdit" wire:loading.attr="disabled" class="rounded-[9px] bg-brand px-5 py-2 text-sm font-bold text-white hover:bg-brand/90 disabled:opacity-60">Enregistrer</button>
                                            <button wire:click="closeEdit" class="rounded-[9px] border border-[#C9D3E6] px-5 py-2 text-sm font-bold text-brand hover:bg-cloud">Annuler</button>
                                        </div>
                                    </div>
                                @endif

                                {{-- Carte membre & QR --}}
                                @if ($qrId === $m['id'] && $qrData !== [])
                                    <div class="mt-3 flex flex-wrap items-center gap-6 rounded-xl bg-[#F8FAFC] p-4" wire:key="qr-{{ $m['id'] }}">
                                        <div
                                            x-data
                                            x-init="window.QRCode && window.QRCode.toCanvas($refs.canvas, '{{ $qrData['url'] }}', { width: 140, margin: 1, color: { dark: '#031D59' } })"
                                            class="rounded-[12px] border border-brand/10 bg-white p-3"
                                        >
                                            <canvas x-ref="canvas"></canvas>
                                        </div>
                                        <div>
                                            <p class="text-[14px] font-bold text-brand">{{ $qrData['nom'] }}</p>
                                            <p class="mt-1 text-xs text-[#5B677A]">Code carte : <span class="font-bold text-brand">{{ $qrData['code'] }}</span></p>
                                            <p class="text-xs text-[#5B677A]">Référence : {{ $qrData['reference'] }}</p>
                                            <a href="{{ $qrData['url'] }}" target="_blank" class="mt-2 inline-flex items-center gap-1.5 rounded-full border border-azure/25 bg-azure/10 px-3.5 py-1.5 text-xs font-semibold text-azure">
                                                <x-ui.icon name="external-link" class="size-3.5" /> Ouvrir la carte membre
                                            </a>
                                            <p class="mt-2 max-w-sm text-[11px] text-[#9AA6B8]">Scanner ce QR code affiche la carte du membre : photo, identité, rôle et référence.</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="rounded-[18px] border border-brand/10 bg-white py-12 text-center text-sm text-[#5B677A]">Aucun compte ne correspond à ces filtres.</p>
            @endforelse
        </div>
    </div>
</div>
