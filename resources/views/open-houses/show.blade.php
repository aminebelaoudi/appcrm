@php
    $start = $openHouse->starts_at->copy()->timezone($openHouse->timezone);
    $end = $openHouse->ends_at->copy()->timezone($openHouse->timezone);
    $photos = $openHouse->photos ?: [];
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Visite libre au {{ $openHouse->address }} le {{ $start->format('d/m/Y') }}.">
    <title>Visite libre | {{ $openHouse->address }}</title>
    <style>
        :root { color-scheme: light; --ink: #20262c; --muted: #647079; --accent: #c8242b; --line: #dbe0e2; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: var(--ink); background: #fff; }
        a { color: inherit; }
        button, input { font: inherit; }
        .site-header { height: 68px; display: flex; align-items: center; justify-content: space-between; gap: 18px; padding: 0 max(24px, calc((100vw - 1160px) / 2)); background: #fff; }
        .brand { display: flex; align-items: center; gap: 12px; min-width: 0; text-decoration: none; font-weight: 700; font-size: 14px; }
        .brand img { display: block; max-width: 150px; max-height: 46px; object-fit: contain; }
        .brand-text { overflow-wrap: anywhere; }
        .header-link { color: var(--accent); font-size: 14px; font-weight: 700; text-decoration: none; white-space: nowrap; }
        .hero { position: relative; height: min(62vh, 640px); min-height: 420px; overflow: hidden; background: #313b3c; }
        .hero img { width: 100%; height: 100%; display: block; object-fit: cover; }
        .hero::after { content: ''; position: absolute; inset: 25% 0 0; background: linear-gradient(transparent, rgba(0,0,0,.68)); pointer-events: none; }
        .hero-content { position: absolute; z-index: 1; bottom: 38px; left: max(24px, calc((100vw - 1160px) / 2)); right: max(24px, calc((100vw - 1160px) / 2)); color: #fff; }
        .eyebrow { display: block; font-size: 13px; font-weight: 700; text-transform: uppercase; margin-bottom: 12px; }
        h1 { font-size: clamp(32px, 4vw, 56px); line-height: 1.06; margin: 0 0 12px; max-width: 900px; overflow-wrap: anywhere; }
        .hero-meta { font-size: 18px; line-height: 1.5; margin: 0; }
        .content { max-width: 1160px; margin: 0 auto; padding: 0 24px; }
        .visit-strip { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 25px 0; border-bottom: 1px solid var(--line); }
        .visit-date { display: flex; gap: 24px; align-items: center; }
        .date-number { color: var(--accent); font-size: 42px; line-height: 1; font-weight: 800; }
        .date-label strong { display: block; font-size: 17px; }
        .date-label span { display: block; color: var(--muted); font-size: 14px; margin-top: 5px; }
        .primary-link, .submit-btn { border: 0; border-radius: 6px; color: #fff; background: var(--accent); text-decoration: none; padding: 14px 20px; font-size: 15px; font-weight: 700; cursor: pointer; display: inline-flex; justify-content: center; align-items: center; min-height: 48px; }
        .primary-link:hover, .submit-btn:hover { background: #aa1d24; }
        .property-section { padding: 54px 0 60px; }
        .section-head { margin-bottom: 24px; }
        .section-head h2 { font-size: 28px; line-height: 1.2; margin: 0 0 8px; }
        .section-head p { margin: 0; color: var(--muted); line-height: 1.55; }
        .facts { display: flex; flex-wrap: wrap; gap: 0; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); margin: 0 0 35px; }
        .fact { padding: 18px 26px 18px 0; margin-right: 28px; min-width: 110px; }
        .fact strong { display: block; font-size: 18px; }
        .fact span { color: var(--muted); font-size: 12px; margin-top: 5px; display: block; }
        .gallery { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .gallery a { display: block; aspect-ratio: 4 / 3; overflow: hidden; background: #edf0f1; }
        .gallery img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .25s; }
        .gallery a:hover img { transform: scale(1.03); }
        .listing-link { display: inline-block; color: var(--accent); margin-top: 20px; font-weight: 700; text-decoration: none; }
        .registration-band { background: #f3f5f5; padding: 62px 0 72px; }
        .registration-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(320px, 440px); gap: 54px; align-items: start; }
        .broker { display: flex; align-items: center; gap: 20px; padding-top: 18px; }
        .broker-photo { width: 96px; height: 96px; flex: 0 0 96px; object-fit: cover; border-radius: 50%; background: #d9dfe0; }
        .broker-placeholder { display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 700; color: #6c767b; }
        .broker strong { display: block; font-size: 20px; margin-bottom: 6px; }
        .broker a, .broker span { display: block; text-decoration: none; color: var(--muted); font-size: 14px; line-height: 1.65; overflow-wrap: anywhere; }
        .form-panel { background: #fff; padding: 28px; border: 1px solid var(--line); border-radius: 8px; }
        .form-panel h2 { font-size: 23px; margin: 0 0 8px; }
        .form-panel p { color: var(--muted); line-height: 1.5; margin: 0 0 23px; font-size: 14px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .field { margin-bottom: 16px; }
        .field label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 7px; }
        .field input { width: 100%; min-height: 46px; padding: 10px 12px; border: 1px solid #c9d0d3; border-radius: 5px; color: var(--ink); background: #fff; }
        .field input:focus { outline: 2px solid rgba(200,36,43,.22); border-color: var(--accent); }
        .consent { display: flex; gap: 10px; color: var(--muted); font-size: 12px; line-height: 1.5; margin: 7px 0 20px; }
        .consent input { margin: 2px 0 0; flex: 0 0 auto; }
        .submit-btn { width: 100%; }
        .submit-btn:disabled { opacity: .55; cursor: wait; }
        .feedback { display: none; padding: 12px; border-radius: 5px; margin-bottom: 16px; font-size: 14px; line-height: 1.5; }
        .feedback.error { display: block; color: #8d1e23; background: #fdebec; }
        .feedback.success { display: block; color: #16623d; background: #e3f5eb; }
        .honeypot { position: absolute; left: -9999px; opacity: 0; pointer-events: none; }
        footer { padding: 26px 24px; text-align: center; color: var(--muted); font-size: 12px; }
        @media (max-width: 800px) { .registration-grid { grid-template-columns: 1fr; gap: 36px; } .gallery { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 580px) { .site-header { height: 62px; } .header-link { font-size: 12px; } .hero { height: 55vh; min-height: 380px; } .hero-content { bottom: 24px; } .hero-meta { font-size: 15px; } .visit-strip { align-items: stretch; flex-direction: column; } .primary-link { width: 100%; } .property-section { padding-top: 38px; } .gallery { grid-template-columns: 1fr 1fr; gap: 6px; } .facts { display: grid; grid-template-columns: 1fr 1fr; } .fact { margin: 0; padding: 14px 8px 14px 0; } .registration-band { padding: 44px 0; } .form-panel { padding: 21px; } .form-row { grid-template-columns: 1fr; gap: 0; } }
    </style>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="#visite">
            @if($openHouse->logo_url)<img src="{{ $openHouse->logo_url }}" alt="Logo">@else<span class="brand-text">{{ $openHouse->broker_name }}</span>@endif
        </a>
        <a class="header-link" href="#inscription">{{ $openHouse->ends_at->isPast() ? 'Voir la visite' : "S'inscrire à la visite" }}</a>
    </header>

    <main>
        <section class="hero" id="visite">
            <img src="{{ $photos[0] ?? '' }}" alt="Extérieur de la propriété au {{ $openHouse->address }}">
            <div class="hero-content">
                <span class="eyebrow">Visite libre · {{ $openHouse->listing_id }}</span>
                <h1>{{ $openHouse->address }}</h1>
                <p class="hero-meta">{{ ucfirst($start->locale('fr')->translatedFormat('l j F Y')) }} · {{ $start->format('H:i') }} à {{ $end->format('H:i') }}</p>
            </div>
        </section>

        <div class="content">
            <div class="visit-strip">
                <div class="visit-date">
                    <div class="date-number">{{ $start->format('d') }}</div>
                    <div class="date-label"><strong>{{ ucfirst($start->locale('fr')->translatedFormat('F Y')) }}</strong><span>De {{ $start->format('H:i') }} à {{ $end->format('H:i') }}</span></div>
                </div>
                <a class="primary-link" href="#inscription">{{ $openHouse->ends_at->isPast() ? 'Voir les informations' : 'Réserver ma place' }}</a>
            </div>

            <section class="property-section" aria-labelledby="property-title">
                <div class="section-head">
                    <h2 id="property-title">Découvrir la propriété</h2>
                    <p>{{ $openHouse->property_type ?: 'Propriété' }}{{ $openHouse->price_label ? ' · '.$openHouse->price_label : '' }}</p>
                </div>
                <div class="facts">
                    @if($openHouse->bedrooms !== null)<div class="fact"><strong>{{ $openHouse->bedrooms }}</strong><span>Chambres</span></div>@endif
                    @if($openHouse->bathrooms !== null)<div class="fact"><strong>{{ $openHouse->bathrooms }}</strong><span>Salles de bain</span></div>@endif
                    @if($openHouse->living_area)<div class="fact"><strong>{{ $openHouse->living_area }}</strong><span>Superficie</span></div>@endif
                    @if($openHouse->price_label)<div class="fact"><strong>{{ $openHouse->price_label }}</strong><span>Prix affiché</span></div>@endif
                </div>
                <div class="gallery">
                    @foreach(array_slice($photos, 1) as $photo)
                        <a href="{{ $photo }}" target="_blank" rel="noopener noreferrer" aria-label="Voir la photo {{ $loop->iteration + 1 }}">
                            <img src="{{ $photo }}" alt="Photo {{ $loop->iteration + 1 }} de la propriété" loading="lazy">
                        </a>
                    @endforeach
                </div>
                @if($openHouse->listing_url)<a class="listing-link" href="{{ $openHouse->listing_url }}" target="_blank" rel="noopener noreferrer">Voir l'inscription ↗</a>@endif
            </section>
        </div>

        <section class="registration-band" id="inscription">
            <div class="content registration-grid">
                <div>
                    <div class="section-head">
                        <h2>Au plaisir de vous rencontrer</h2>
                        <p>Inscrivez-vous pour cette visite libre au {{ $openHouse->address }}.</p>
                    </div>
                    @if($openHouse->note)<p>{{ $openHouse->note }}</p>@endif
                    <div class="broker">
                        @if($openHouse->broker_photo_url)<img class="broker-photo" src="{{ $openHouse->broker_photo_url }}" alt="Portrait de {{ $openHouse->broker_name }}">
                        @else<div class="broker-photo broker-placeholder" aria-hidden="true">{{ strtoupper(substr($openHouse->broker_name, 0, 1)) }}</div>@endif
                        <div>
                            <strong>{{ $openHouse->broker_name }}</strong>
                            @if($openHouse->broker_phone)<a href="tel:{{ $openHouse->broker_phone }}">{{ $openHouse->broker_phone }}</a>@endif
                            @if($openHouse->broker_email)<a href="mailto:{{ $openHouse->broker_email }}">{{ $openHouse->broker_email }}</a>@endif
                        </div>
                    </div>
                </div>
                @if($openHouse->ends_at->isPast())
                    <div class="form-panel"><h2>Visite terminée</h2><p>Les inscriptions pour cette visite libre sont fermées.</p></div>
                @else
                <form class="form-panel" id="registrationForm" action="{{ route('open-houses.register', $openHouse->public_token) }}" method="post">
                    @csrf
                    <h2>Inscription à la visite</h2>
                    <p>Remplissez vos coordonnées pour confirmer votre présence.</p>
                    <div class="feedback" id="registrationFeedback" role="status" aria-live="polite"></div>
                    <div class="form-row">
                        <div class="field"><label for="firstName">Prénom</label><input id="firstName" name="firstName" autocomplete="given-name" required maxlength="100"></div>
                        <div class="field"><label for="lastName">Nom</label><input id="lastName" name="lastName" autocomplete="family-name" required maxlength="100"></div>
                    </div>
                    <div class="field"><label for="email">Courriel</label><input id="email" name="email" type="email" autocomplete="email" required maxlength="255"></div>
                    <div class="field"><label for="phone">Téléphone</label><input id="phone" name="phone" type="tel" autocomplete="tel" required maxlength="40"></div>
                    <div class="honeypot" aria-hidden="true"><label for="website">Site web</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
                    <label class="consent"><input type="checkbox" name="consent" value="1" required><span>J'accepte d'être contacté au sujet de cette visite libre.</span></label>
                    <button class="submit-btn" id="submitButton" type="submit">Confirmer mon inscription</button>
                </form>
                @endif
            </div>
        </section>
    </main>
    <footer>Visite libre · {{ $openHouse->address }}</footer>
    @if(!$openHouse->ends_at->isPast())
    <script>
        const form = document.getElementById('registrationForm');
        const feedback = document.getElementById('registrationFeedback');
        const submitButton = document.getElementById('submitButton');
        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            submitButton.disabled = true;
            submitButton.textContent = 'Inscription en cours...';
            feedback.className = 'feedback';
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: new FormData(form),
                    credentials: 'same-origin'
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Impossible de confirmer votre inscription.');
                feedback.textContent = 'Votre inscription est confirmée. À bientôt!';
                feedback.className = 'feedback success';
                form.reset();
            } catch (error) {
                feedback.textContent = error.message || 'Une erreur est survenue. Veuillez réessayer.';
                feedback.className = 'feedback error';
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Confirmer mon inscription';
            }
        });
    </script>
    @endif
</body>
</html>
