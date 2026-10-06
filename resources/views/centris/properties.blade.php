<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy" content="frame-ancestors *">
    <title>Mes inscriptions</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }

        /* Loader overlay */
        .loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 1;
            transition: opacity 0.4s ease;
        }

        .loader-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .loader-content {
            text-align: center;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .loader-spinner {
            width: 60px;
            height: 60px;
            margin: 0 auto 24px;
            position: relative;
        }

        .loader-spinner::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border: 4px solid #e5e7eb;
            border-radius: 50%;
        }

        .loader-spinner::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border: 4px solid transparent;
            border-top-color: #ed2227;
            border-right-color: #ed2227;
            border-radius: 50%;
            animation: spin 0.8s cubic-bezier(0.4, 0, 0.2, 1) infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .loader-title {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 12px;
        }

        .loader-message {
            font-size: 15px;
            color: #6b7280;
            font-weight: 500;
            min-height: 24px;
            animation: fadeInOut 2s ease-in-out infinite;
        }

        @keyframes fadeInOut {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }

        .loader-dots {
            display: inline-flex;
            gap: 4px;
            margin-left: 4px;
        }

        .loader-dot {
            width: 4px;
            height: 4px;
            background: #ed2227;
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out;
        }

        .loader-dot:nth-child(1) { animation-delay: -0.32s; }
        .loader-dot:nth-child(2) { animation-delay: -0.16s; }

        @keyframes bounce {
            0%, 80%, 100% {
                transform: scale(0);
                opacity: 0.5;
            }
            40% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }

        .filters-bar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-container {
            flex: 1;
            min-width: 300px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 45px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #e31c23;
            box-shadow: 0 0 0 3px rgba(227, 28, 35, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 18px;
        }

        .clear-search {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #999;
            font-size: 20px;
            cursor: pointer;
            display: none;
        }

        .clear-search:hover {
            color: #e31c23;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-label {
            font-size: 14px;
            color: #666;
            font-weight: 600;
        }

        .custom-select-wrapper {
            position: relative;
            min-width: 200px;
        }

        .custom-select {
            width: 100%;
            padding: 12px 40px 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            color: #333;
            background: linear-gradient(to bottom, white, #fafafa);
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .custom-select:hover {
            border-color: #e31c23;
            box-shadow: 0 2px 8px rgba(227, 28, 35, 0.1);
        }

        .custom-select.open {
            border-color: #e31c23;
            box-shadow: 0 0 0 4px rgba(227, 28, 35, 0.1);
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        .select-arrow {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 10px;
            pointer-events: none;
            transition: all 0.3s;
        }

        .custom-select:hover ~ .select-arrow,
        .custom-select.open ~ .select-arrow {
            color: #e31c23;
        }

        .custom-select.open ~ .select-arrow {
            transform: translateY(-50%) rotate(180deg);
        }

        .select-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e31c23;
            border-top: none;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            max-height: 250px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            /* Empêcher le scroll de la page quand on atteint la fin de la liste */
            overscroll-behavior: contain;
        }

        .select-dropdown.open {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .select-option {
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .select-option:hover {
            background: linear-gradient(to right, #fff5f5, #ffe8e8);
            color: #e31c23;
            padding-left: 20px;
        }

        .select-option.selected {
            background: #e31c23;
            color: white;
            font-weight: 600;
        }

        .select-option.selected:hover {
            background: #c71a1f;
        }

        .select-option::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: transparent;
        }

        .select-option.selected::before {
            background: white;
        }

        /* Recherche dans le dropdown Courtier */
        .broker-search {
            position: sticky;
            top: 0;
            background: white;
            padding: 8px;
            border-bottom: 1px solid #eee;
            z-index: 5;
        }

        .broker-search-input {
            width: 100%;
            padding: 8px 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }

        .broker-search-input:focus {
            outline: none;
            border-color: #e31c23;
            box-shadow: 0 0 0 3px rgba(227, 28, 35, 0.1);
        }


        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 25px;
            padding: 10px;
        }

        .property-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s, border 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            border-color: #e31c23;
        }

        .image-container {
            position: relative;
            width: 100%;
            height: 250px;
            overflow: hidden;
            background-color: #e0e0e0;
        }

        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .status-badge-overlay {
            position: absolute;
            top: 10px;
            right: 10px;
            background: white;
            color: #333;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .status-badge-overlay::before {
            content: '●';
            font-size: 12px;
            line-height: 1;
        }

        .status-sold::before {
            color: #4caf50;
        }

        .status-active-overlay::before {
            color: #2196f3;
        }

        .status-pending::before {
            color: #ff9800;
        }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.6);
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 20px;
            z-index: 10;
        }

        .carousel-nav:hover {
            background: rgba(0,0,0,0.8);
        }

        .carousel-prev {
            left: 10px;
        }

        .carousel-next {
            right: 10px;
        }

        .carousel-image {
            display: none;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .carousel-image.active {
            display: block;
        }

        .carousel-dots {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 10;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: background 0.3s;
        }

        .dot.active {
            background: rgba(255, 255, 255, 1);
        }

        .property-info {
            padding: 20px;
        }

        .price {
            color: #e31c23;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .address {
            font-size: 16px;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .property-type {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }

        .features {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #1a1a1a;
            font-size: 13px;
            font-weight: 600;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 8px 14px;
            border-radius: 20px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .feature img {
            transition: transform 0.3s ease;
        }


        .feature svg {
            width: 24px;
            height: 24px;
            fill: #333;
        }

        .social-post-button {
            width: 100%;
            margin-top: 16px;
            border: 2px solid #e31c23;
            background: #fff;
            color: #e31c23;
            border-radius: 8px;
            padding: 11px 14px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .social-post-button:hover {
            background: #e31c23;
            color: #fff;
        }

        .social-post-button svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
        }

        .social-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.55);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 10000;
        }

        .social-modal-overlay.open {
            display: flex;
        }

        .social-modal {
            width: min(680px, 100%);
            max-height: 90vh;
            overflow-y: auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.25);
        }

        .social-modal-header,
        .social-modal-footer {
            padding: 18px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            border-bottom: 1px solid #e5e7eb;
        }

        .social-modal-footer {
            border-top: 1px solid #e5e7eb;
            border-bottom: 0;
            justify-content: flex-end;
        }

        .social-modal-title {
            font-size: 20px;
            font-weight: 800;
            color: #1f2937;
        }

        .social-modal-close {
            border: 0;
            background: #f3f4f6;
            color: #4b5563;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            font-size: 22px;
            cursor: pointer;
        }

        .social-modal-body {
            padding: 22px;
        }

        .social-modal-section {
            margin-bottom: 20px;
        }

        .social-modal-label {
            display: block;
            font-size: 13px;
            font-weight: 800;
            color: #374151;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .social-account-list {
            display: grid;
            gap: 10px;
        }

        .social-account-row {
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            background: #fff;
        }

        .social-account-row.disabled {
            background: #f9fafb;
            opacity: 0.68;
        }

        .social-account-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            background: #e5e7eb;
            flex: 0 0 auto;
        }

        .social-account-main {
            flex: 1;
            min-width: 0;
        }

        .social-account-name {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
            overflow-wrap: anywhere;
        }

        .social-account-meta {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
            text-transform: capitalize;
        }

        .social-account-status {
            font-size: 12px;
            color: #b91c1c;
            font-weight: 700;
        }

        .social-datetime-input {
            width: 100%;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 15px;
        }

        .social-summary-preview {
            width: 100%;
            min-height: 190px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            resize: vertical;
            font-size: 14px;
            line-height: 1.5;
            color: #374151;
            background: #f9fafb;
        }

        .social-modal-alert {
            display: none;
            border-radius: 8px;
            padding: 11px 12px;
            margin-bottom: 16px;
            font-size: 14px;
            font-weight: 600;
        }

        .social-modal-alert.error {
            display: block;
            color: #991b1b;
            background: #fee2e2;
            border: 1px solid #fecaca;
        }

        .social-modal-alert.success {
            display: block;
            color: #166534;
            background: #dcfce7;
            border: 1px solid #bbf7d0;
        }

        .social-modal-note {
            color: #6b7280;
            font-size: 13px;
            margin-top: 8px;
        }

        .social-modal-btn {
            border: 0;
            border-radius: 8px;
            padding: 11px 16px;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
        }

        .social-modal-btn.secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .social-modal-btn.primary {
            background: #e31c23;
            color: #fff;
        }

        .social-modal-btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .no-properties {
            text-align: center;
            padding: 50px;
            color: #666;
            font-size: 18px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 40px;
            padding: 20px;
        }

        .pagination a,
        .pagination span {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background-color: #e31c23;
            color: white;
            border-color: #e31c23;
        }

        .pagination .active {
            background-color: #e31c23;
            color: white;
            border-color: #e31c23;
        }

        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .pagination-info {
            text-align: center;
            color: #666;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <!-- Loader overlay - visible immédiatement au chargement de la page -->
    <div class="loader-overlay" id="pageLoader" style="display: flex;">
        <div class="loader-content">
            <div class="loader-spinner"></div>
            <div class="loader-title">Chargement en cours</div>
            <div class="loader-message" id="loaderMessage">
                Récupération de vos propriétés
                <span class="loader-dots">
                    <span class="loader-dot"></span>
                    <span class="loader-dot"></span>
                    <span class="loader-dot"></span>
                </span>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- <h2>Mes inscriptions</h2> -->
        
        <!-- Barre de recherche et filtres -->
        <div class="filters-bar">
            <div class="search-container">
                <span class="search-icon">🔍</span>
                  <input type="text" 
                      class="search-input" 
                      id="searchInput" 
                      placeholder="Rechercher par adresse,lms..."
                      value="{{ $search ?? '' }}"
                      oninput="scheduleSearch()">
                <button class="clear-search" id="clearSearch" onclick="clearSearch()">×</button>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Statut:</label>
                <div class="custom-select-wrapper">
                    <div class="custom-select" onclick="toggleDropdown()" id="customSelect">
                        <span id="selectedText">Tous les statuts</span>
                    </div>
                    <span class="select-arrow">▼</span>
                    <div class="select-dropdown" id="selectDropdown">
                        <div class="select-option selected" data-value="" onclick="selectOption('', 'Tous les statuts')">
                            Tous les statuts
                        </div>
                        <div class="select-option" data-value="active" onclick="selectOption('active', 'En vigueur')">
                            En vigueur
                        </div>
                        <div class="select-option" data-value="sold" onclick="selectOption('sold', 'Vendu')">
                            Vendu
                        </div>
                        <div class="select-option" data-value="pending" onclick="selectOption('pending', 'Hors marché')">
                            Hors marché
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtre Courtier -->
            <div class="filter-group">
                <label class="filter-label">Courtier:</label>
                <div class="custom-select-wrapper">
                    <div class="custom-select" onclick="toggleBrokerDropdown()" id="brokerSelect">
                        @php
                            $selectedBrokerName = 'Tous les courtiers';
                            if (!empty($selectedMemberKey ?? null) && !empty($brokers ?? [])) {
                                foreach ($brokers as $b) {
                                    if (($b['MemberKey'] ?? null) == $selectedMemberKey) { $selectedBrokerName = $b['MemberFullName'] ?? $selectedBrokerName; break; }
                                }
                            }
                        @endphp
                        <span id="selectedBrokerText">{{ $selectedBrokerName }}</span>
                    </div>
                    <span class="select-arrow">▼</span>
                    <div class="select-dropdown" id="brokerDropdown">
                        <div class="broker-search">
                            <input type="text" id="brokerSearchInput" class="broker-search-input" placeholder="Rechercher un courtier...">
                        </div>
                        <div class="select-option {{ empty($selectedMemberKey ?? '') ? 'selected' : '' }}" data-value="" onclick="selectBroker('', 'Tous les courtiers')">
                            Tous les courtiers
                        </div>
                        @if(!empty($brokers ?? []))
                            @foreach($brokers as $b)
                                <div class="select-option {{ ($selectedMemberKey ?? '') === ($b['MemberKey'] ?? '') ? 'selected' : '' }}" data-value="{{ $b['MemberKey'] ?? '' }}" data-name="{{ strtolower($b['MemberFullName'] ?? '') }}" onclick="selectBroker('{{ $b['MemberKey'] ?? '' }}', '{{ addslashes($b['MemberFullName'] ?? 'Sans nom') }}')">
                                    {{ $b['MemberFullName'] ?? 'Sans nom' }}
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        
        </div>
        
        @if(count($properties) > 0)
            <div class="properties-grid">
                @foreach($properties as $property)
                    <div class="property-card" data-property-id="{{ $property['ListingKey'] ?? '' }}" onclick="navigateToProperty('{{ route('centris.property.details', ['listingKey' => $property['ListingKey'] ?? '', 'locationId' => $locationId]) }}')" >
                        <div class="image-container">
                            @if(isset($property['Media']) && count($property['Media']) > 0)
                                @foreach($property['Media'] as $index => $media)
                                    <img src="{{ $media['MediaURL'] ?? '' }}" 
                                         alt="{{ $media['ImageOf'] ?? 'Propriété' }}" 
                                         class="carousel-image {{ $index === 0 ? 'active' : '' }}"
                                         data-index="{{ $index }}">
                                @endforeach
                                @if(count($property['Media']) > 1)
                                    <button class="carousel-nav carousel-prev" onclick="event.stopPropagation(); changeImage(this, -1)">‹</button>
                                    <button class="carousel-nav carousel-next" onclick="event.stopPropagation(); changeImage(this, 1)">›</button>
                                    <div class="carousel-dots">
                                        @foreach($property['Media'] as $index => $media)
                                            <span class="dot {{ $index === 0 ? 'active' : '' }}" onclick="event.stopPropagation(); goToImage(this, {{ $index }})"></span>
                                        @endforeach
                                    </div>
                                @endif
                            @else
                                <img src="https://via.placeholder.com/400x250?text=Aucune+Image" alt="Aucune image" class="carousel-image active">
                            @endif
                            
                            @php
                                $status = strtolower($property['MlsStatus'] ?? 'active');
                                $statusClass = 'status-active-overlay';
                                $statusText = 'En vigueur';
                                
                                if (in_array($status, ['sold', 'vendue', 'vendu'])) {
                                    $statusClass = 'status-sold';
                                    $statusText = 'Vendu';
                                } elseif (in_array($status, ['pending', 'en attente'])) {
                                    $statusClass = 'status-pending';
                                    $statusText = 'Hors marché';
                                } elseif ($status === 'active') {
                                    $statusText = 'En vigueur';
                                }
                            @endphp
                            <div class="status-badge-overlay {{ $statusClass }}">{{ $statusText }}</div>
                        </div>
                        
                        <div class="property-info">
                            @php
                                $addressParts = [];
                                
                                // Numéro de rue
                                if (!empty($property['StreetNumberStart'])) {
                                    $streetNumber = $property['StreetNumberStart'];
                                    if (!empty($property['StreetNumberEnd'])) {
                                        $streetNumber .= ' - ' . $property['StreetNumberEnd'];
                                    }
                                    $addressParts[] = $streetNumber;
                                }
                                
                                // Nom de rue
                                if (!empty($property['StreetShortName'])) {
                                    $addressParts[] = $property['StreetShortName'];
                                }
                                
                                // Ville
                                if (!empty($property['Township'])) {
                                    $addressParts[] = $property['Township'];
                                }
                                
                                // Code postal
                                if (!empty($property['PostalCode'])) {
                                    $addressParts[] = $property['PostalCode'];
                                }
                                
                                $fullAddress = implode(', ', $addressParts);
                            @endphp                    
                            <div class="address">{{ $fullAddress }}</div>
                         
                            <div class="features">
                                <div class="feature">
                                    <img src="{{ asset('img/contact.svg') }}" alt="Contacts" style="width: 18px; height: 18px; filter: brightness(0) opacity(0.7);">
                                    <span>{{ $property['PersonsCount'] ?? 0 }}</span>
                                </div>
                                
                                <div class="feature">
                                    <img src="{{ asset('img/opportunity.svg') }}" alt="Opportunités" style="width: 18px; height: 18px; filter: brightness(0) opacity(0.7);">
                                    <span>{{ $property['OpportunitiesCount'] ?? 0 }} Opp</span>
                                </div>

                                <div class="feature">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M19,3H5C3.9,3 3,3.9 3,5V19C3,20.1 3.9,21 5,21H19C20.1,21 21,20.1 21,19V5C21,3.9 20.1,3 19,3M19,19H5V5H19V19M7,7H17V9H7V7M7,11H17V13H7V11M7,15H13V17H7V15Z"/>
                                    </svg>
                                    <span>{{ $property['CentrisSubmissionsCount'] ?? 0 }} Soum</span>
                                </div>
                            </div>
                            @if(($locationId ?? '') === 'FqqkdWQ0F0QPYOpLYXnz')
                                <button type="button" class="social-post-button" onclick="openSocialPostModal(event, '{{ $property['ListingKey'] ?? '' }}')">
                                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" aria-hidden="true">
                                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                        <path d="M16 2v4M8 2v4M3 10h18M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01"></path>
                                    </svg>
                                    <span>Planifier un post</span>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="no-properties">
                Aucune propriété disponible pour le moment.
            </div>
        @endif

        @if(isset($pagination) && $pagination['total_pages'] > 1)
            <div class="pagination">
                @if($pagination['has_prev'])
                    <a href="?locationId={{ $locationId }}&page={{ $pagination['current_page'] - 1 }}{{ !empty($selectedMemberKey) ? '&memberKey='.$selectedMemberKey : '' }}{{ !empty($search ?? '') ? '&search='.urlencode($search) : '' }}">‹ Précédent</a>
                @else
                    <span class="disabled">‹ Précédent</span>
                @endif

                @for($i = 1; $i <= $pagination['total_pages']; $i++)
                    @if($i == $pagination['current_page'])
                        <span class="active">{{ $i }}</span>
                    @else
                        <a href="?locationId={{ $locationId }}&page={{ $i }}{{ !empty($selectedMemberKey) ? '&memberKey='.$selectedMemberKey : '' }}{{ !empty($search ?? '') ? '&search='.urlencode($search) : '' }}">{{ $i }}</a>
                    @endif
                @endfor

                @if($pagination['has_next'])
                    <a href="?locationId={{ $locationId }}&page={{ $pagination['current_page'] + 1 }}{{ !empty($selectedMemberKey) ? '&memberKey='.$selectedMemberKey : '' }}{{ !empty($search ?? '') ? '&search='.urlencode($search) : '' }}">Suivant ›</a>
                @else
                    <span class="disabled">Suivant ›</span>
                @endif
            </div>
            <div class="pagination-info">
                Affichage de {{ (($pagination['current_page'] - 1) * $pagination['per_page']) + 1 }} à {{ min($pagination['current_page'] * $pagination['per_page'], $pagination['total_count']) }} sur {{ $pagination['total_count'] }} propriétés
            </div>
        @endif
    </div>

    <div class="social-modal-overlay" id="socialPostModal" role="dialog" aria-modal="true" aria-labelledby="socialPostModalTitle">
        <div class="social-modal" onclick="event.stopPropagation()">
            <div class="social-modal-header">
                <div class="social-modal-title" id="socialPostModalTitle">Planifier un post</div>
                <button type="button" class="social-modal-close" onclick="closeSocialPostModal()" aria-label="Fermer">×</button>
            </div>
            <div class="social-modal-body">
                <div class="social-modal-alert" id="socialPostAlert"></div>

                <div class="social-modal-section">
                    <label class="social-modal-label">Comptes Facebook et Instagram</label>
                    <div class="social-account-list" id="socialPostAccounts">
                        <div class="social-modal-note">Chargement des comptes...</div>
                    </div>
                </div>

                <div class="social-modal-section">
                    <label class="social-modal-label" for="socialPostScheduleDate">Date et heure de publication</label>
                    <input type="datetime-local" class="social-datetime-input" id="socialPostScheduleDate">
                    <div class="social-modal-note">La date doit être dans le futur. Elle sera envoyée à GHL en UTC.</div>
                </div>

                <div class="social-modal-section">
                    <label class="social-modal-label" for="socialPostSummary">Description générée</label>
                    <textarea class="social-summary-preview" id="socialPostSummary" readonly></textarea>
                    <div class="social-modal-note" id="socialPostMediaNote"></div>
                </div>
            </div>
            <div class="social-modal-footer">
                <button type="button" class="social-modal-btn secondary" onclick="closeSocialPostModal()">Annuler</button>
                <button type="button" class="social-modal-btn primary" id="socialPostSubmitBtn" onclick="submitSocialPost()">Planifier</button>
            </div>
        </div>
    </div>

    <script>
    let currentStatusFilter = '';
    let searchTimer = null;
    let socialPostListingKey = null;
    const socialPostLocationId = @json($locationId);

        function getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }

        function escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, function(char) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                }[char];
            });
        }

        function setSocialPostAlert(message, type = 'error') {
            const alert = document.getElementById('socialPostAlert');
            if (!alert) return;
            alert.textContent = message || '';
            alert.className = `social-modal-alert ${message ? type : ''}`;
        }

        function setSocialPostLoading(isLoading) {
            const submitBtn = document.getElementById('socialPostSubmitBtn');
            if (!submitBtn) return;
            submitBtn.disabled = isLoading;
            submitBtn.textContent = isLoading ? 'Planification...' : 'Planifier';
        }

        function toLocalDateTimeInputValue(date) {
            const offsetDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
            return offsetDate.toISOString().slice(0, 16);
        }

        function getAccountUnavailableReason(account) {
            if (account.deleted) return 'Supprimé';
            if (account.isExpired) return 'Connexion expirée';
            if (!account.active) return 'Inactif';
            return 'Non disponible';
        }

        function renderSocialAccounts(accounts) {
            const container = document.getElementById('socialPostAccounts');
            if (!container) return;

            if (!accounts.length) {
                container.innerHTML = '<div class="social-modal-note">Aucun compte Facebook ou Instagram trouvé pour cette location.</div>';
                return;
            }

            container.innerHTML = accounts.map((account, index) => {
                const disabled = !account.selectable;
                const checked = account.selectable ? 'checked' : '';
                const unavailable = disabled ? `<div class="social-account-status">${getAccountUnavailableReason(account)}</div>` : '';
                const avatar = account.avatar
                    ? `<img class="social-account-avatar" src="${escapeHtml(account.avatar)}" alt="">`
                    : '<div class="social-account-avatar"></div>';
                const accountName = escapeHtml(account.name || 'Compte sans nom');
                const platform = escapeHtml(account.platform || '');
                const accountType = account.type ? ' · ' + escapeHtml(account.type) : '';
                const accountId = escapeHtml(account.id);

                return `
                    <label class="social-account-row ${disabled ? 'disabled' : ''}">
                        <input type="checkbox" name="socialAccountIds" value="${accountId}" ${checked} ${disabled ? 'disabled' : ''}>
                        ${avatar}
                        <div class="social-account-main">
                            <div class="social-account-name">${accountName}</div>
                            <div class="social-account-meta">${platform}${accountType}</div>
                            ${unavailable}
                        </div>
                    </label>
                `;
            }).join('');
        }

        function resetSocialPostModal() {
            setSocialPostAlert('');
            setSocialPostLoading(false);
            document.getElementById('socialPostAccounts').innerHTML = '<div class="social-modal-note">Chargement des comptes...</div>';
            document.getElementById('socialPostSummary').value = '';
            document.getElementById('socialPostMediaNote').textContent = '';
            const scheduleInput = document.getElementById('socialPostScheduleDate');
            const defaultDate = new Date();
            defaultDate.setDate(defaultDate.getDate() + 1);
            defaultDate.setHours(10, 0, 0, 0);
            scheduleInput.value = toLocalDateTimeInputValue(defaultDate);
            scheduleInput.min = toLocalDateTimeInputValue(new Date(Date.now() + 5 * 60000));
        }

        async function openSocialPostModal(event, listingKey) {
            event.stopPropagation();
            socialPostListingKey = listingKey;
            resetSocialPostModal();

            const modal = document.getElementById('socialPostModal');
            modal.classList.add('open');

            try {
                const params = new URLSearchParams({ locationId: socialPostLocationId || '' });
                const response = await fetch(`/api/properties/${encodeURIComponent(listingKey)}/social-accounts?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Impossible de charger les comptes sociaux.');
                }

                renderSocialAccounts(data.accounts || []);
                document.getElementById('socialPostSummary').value = data.summary || '';
                document.getElementById('socialPostMediaNote').textContent = `${data.mediaCount || 0} image(s) seront utilisées, maximum 5.`;

                const hasSelectableAccount = (data.accounts || []).some(account => account.selectable);
                if (!hasSelectableAccount) {
                    setSocialPostAlert('Aucun compte Facebook ou Instagram actif n’est disponible pour cette location.', 'error');
                }
            } catch (error) {
                renderSocialAccounts([]);
                setSocialPostAlert(error.message || 'Erreur lors du chargement de la planification.', 'error');
            }
        }

        function closeSocialPostModal() {
            document.getElementById('socialPostModal')?.classList.remove('open');
            socialPostListingKey = null;
        }

        async function submitSocialPost() {
            const selectedAccountIds = Array.from(document.querySelectorAll('input[name="socialAccountIds"]:checked'))
                .map(input => input.value)
                .filter(Boolean);
            const scheduleInput = document.getElementById('socialPostScheduleDate');
            const localSchedule = scheduleInput.value;

            setSocialPostAlert('');

            if (!selectedAccountIds.length) {
                setSocialPostAlert('Sélectionnez au moins un compte actif.');
                return;
            }

            if (!localSchedule || new Date(localSchedule) <= new Date()) {
                setSocialPostAlert('Choisissez une date et une heure dans le futur.');
                return;
            }

            setSocialPostLoading(true);

            try {
                const response = await fetch(`/api/properties/${encodeURIComponent(socialPostListingKey)}/social-posts`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': getCsrfToken()
                    },
                    body: JSON.stringify({
                        locationId: socialPostLocationId,
                        accountIds: selectedAccountIds,
                        scheduleDate: new Date(localSchedule).toISOString()
                    })
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Impossible de planifier le post.');
                }

                setSocialPostAlert('Post planifié avec succès.', 'success');
                setTimeout(() => closeSocialPostModal(), 1200);
            } catch (error) {
                setSocialPostAlert(error.message || 'Erreur lors de la planification du post.');
            } finally {
                setSocialPostLoading(false);
            }
        }

        document.getElementById('socialPostModal')?.addEventListener('click', closeSocialPostModal);

        // Utility to show the page loader with a custom message
        function showPageLoader(message) {
            const overlay = document.getElementById('pageLoader');
            const messageEl = document.getElementById('loaderMessage');
            if (!overlay) return;
            if (messageEl && message) {
                messageEl.innerHTML = `${message} <span class="loader-dots"><span class="loader-dot"></span><span class="loader-dot"></span><span class="loader-dot"></span></span>`;
            }
            overlay.classList.remove('hidden');
            overlay.style.opacity = '1';
            overlay.style.pointerEvents = 'auto';
        }

        function toggleDropdown() {
            const select = document.getElementById('customSelect');
            const dropdown = document.getElementById('selectDropdown');
            
            select.classList.toggle('open');
            dropdown.classList.toggle('open');
        }

        function selectOption(value, text) {
            // Mettre à jour l'affichage
            document.getElementById('selectedText').textContent = text;
            
            // Mettre à jour la sélection visuelle
            document.querySelectorAll('.select-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            event.target.classList.add('selected');
            
            // Fermer le dropdown
            document.getElementById('customSelect').classList.remove('open');
            document.getElementById('selectDropdown').classList.remove('open');
            
            // Mettre à jour le filtre et appliquer
            currentStatusFilter = value;
            filterProperties();
        }

        // Fermer le dropdown si on clique ailleurs
        document.addEventListener('click', function(event) {
            const wrapper = event.target.closest('.custom-select-wrapper');
            if (!wrapper) {
                document.getElementById('customSelect')?.classList.remove('open');
                document.getElementById('selectDropdown')?.classList.remove('open');
                document.getElementById('brokerSelect')?.classList.remove('open');
                document.getElementById('brokerDropdown')?.classList.remove('open');
            }
        });

        function filterProperties() {
            const cards = document.querySelectorAll('.property-card');
            cards.forEach(card => {
                const statusBadge = card.querySelector('.status-badge-overlay')?.textContent.toLowerCase() || '';
                let cardStatus = '';
                if (statusBadge.includes('vendu')) cardStatus = 'sold';
                else if (statusBadge.includes('hors marché') || statusBadge.includes('hors marche')) cardStatus = 'pending';
                else if (statusBadge.includes('en vigueur')) cardStatus = 'active';

                const matchesStatus = !currentStatusFilter || cardStatus === currentStatusFilter;
                card.style.display = matchesStatus ? '' : 'none';
            });
        }
        
        function clearSearch() {
            const input = document.getElementById('searchInput');
            if (input) input.value = '';
            applySearch('');
        }

        function scheduleSearch() {
            const input = document.getElementById('searchInput');
            const value = input ? input.value.trim() : '';

            const clearBtn = document.getElementById('clearSearch');
            if (clearBtn) clearBtn.style.display = value ? 'block' : 'none';

            if (searchTimer) clearTimeout(searchTimer);
            searchTimer = setTimeout(() => applySearch(value), 400);
        }

        function applySearch(value) {
            showPageLoader(value ? 'Recherche des propriétés' : 'Chargement de toutes les propriétés');
            const params = new URLSearchParams(window.location.search);
            params.set('locationId', '{{ $locationId }}');
            params.delete('page');
            if (value) {
                params.set('search', value);
            } else {
                params.delete('search');
            }
            window.location.search = params.toString();
        }

        function changeImage(button, direction) {
            const card = button.closest('.property-card');
            const images = card.querySelectorAll('.carousel-image');
            const dots = card.querySelectorAll('.dot');
            let currentIndex = Array.from(images).findIndex(img => img.classList.contains('active'));
            
            // Retirer la classe active de l'image et du point actuels
            images[currentIndex].classList.remove('active');
            if (dots[currentIndex]) {
                dots[currentIndex].classList.remove('active');
            }
            
            // Calculer le nouvel index
            currentIndex = (currentIndex + direction + images.length) % images.length;
            
            // Ajouter la classe active à la nouvelle image et au point
            images[currentIndex].classList.add('active');
            if (dots[currentIndex]) {
                dots[currentIndex].classList.add('active');
            }
        }
        
        function goToImage(dotElement, index) {
            const card = dotElement.closest('.property-card');
            const images = card.querySelectorAll('.carousel-image');
            const dots = card.querySelectorAll('.dot');
            
            // Retirer toutes les classes active
            images.forEach(img => img.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            // Ajouter la classe active à l'image et au point sélectionnés
            images[index].classList.add('active');
            dots[index].classList.add('active');
        }

        // === Courtier (broker) filter ===
        function toggleBrokerDropdown() {
            const select = document.getElementById('brokerSelect');
            const dropdown = document.getElementById('brokerDropdown');
            select.classList.toggle('open');
            dropdown.classList.toggle('open');
            if (dropdown.classList.contains('open')) {
                const input = document.getElementById('brokerSearchInput');
                if (input) {
                    input.value = '';
                    // Réinitialiser l'affichage de toutes les options
                    filterBrokerOptions('');
                    setTimeout(() => input.focus(), 0);
                }
            }
        }

        function selectBroker(memberKey, displayName) {
            // UI update
            const label = document.getElementById('selectedBrokerText');
            if (label) label.textContent = displayName;
            document.querySelectorAll('#brokerDropdown .select-option').forEach(opt => opt.classList.remove('selected'));
            const active = Array.from(document.querySelectorAll('#brokerDropdown .select-option')).find(o => o.getAttribute('data-value') === memberKey);
            if (active) active.classList.add('selected');
            document.getElementById('brokerSelect')?.classList.remove('open');
            document.getElementById('brokerDropdown')?.classList.remove('open');

            // Show loader while navigating to filtered results
            showPageLoader(memberKey ? 'Filtrage des propriétés par courtier' : 'Chargement de toutes les propriétés');

            // Build URL params: keep locationId, reset page, set/remove memberKey
            const params = new URLSearchParams(window.location.search);
            params.set('locationId', '{{ $locationId }}');
            params.delete('page');
            if (memberKey) {
                params.set('memberKey', memberKey);
            } else {
                params.delete('memberKey');
            }
            window.location.search = params.toString();
        }

        // Navigate to property details with loader
        function navigateToProperty(url) {
            showPageLoader('Ouverture de la fiche propriété');
            window.location.href = url;
        }

        // Filtrer les courtiers par nom
        function filterBrokerOptions(term) {
            const options = Array.from(document.querySelectorAll('#brokerDropdown .select-option'));
            // Garder toujours visible la première option (Tous les courtiers)
            const first = options.shift();
            if (first) first.style.display = '';
            const value = (term || '').toLowerCase();
            options.forEach(opt => {
                const name = (opt.getAttribute('data-name') || opt.textContent || '').toLowerCase();
                opt.style.display = !value || name.includes(value) ? '' : 'none';
            });
        }

        document.addEventListener('input', function(e) {
            if (e.target && e.target.id === 'brokerSearchInput') {
                filterBrokerOptions(e.target.value);
            }
        });

        (function() {
            const input = document.getElementById('searchInput');
            const clearBtn = document.getElementById('clearSearch');
            const value = input ? input.value.trim() : '';
            if (clearBtn) clearBtn.style.display = value ? 'block' : 'none';
        })();

        // === Gestion du loader de page ===
        (function() {
            const loaderMessages = [
                'Récupération de vos propriétés',
                'Connexion aux APIs Centris',
                'Chargement des médias et photos',
                'Synchronisation avec GHL',
                'Chargement des contacts',
                'Finalisation du chargement'
            ];
            
            let messageIndex = 0;
            const loaderMessageElement = document.querySelector('.loader-message');
            const loaderOverlay = document.getElementById('pageLoader');
            
            // Rotation des messages toutes les 2.5 secondes
            const messageInterval = setInterval(() => {
                messageIndex = (messageIndex + 1) % loaderMessages.length;
                if (loaderMessageElement) {
                    // Extraire juste le texte sans les dots
                    const dotsHtml = '<span class="loader-dots"><span>.</span><span>.</span><span>.</span></span>';
                    loaderMessageElement.innerHTML = loaderMessages[messageIndex] + dotsHtml;
                }
            }, 2500);
            
            // Cacher (mais ne pas supprimer) le loader quand le contenu est chargé
            window.addEventListener('load', () => {
                clearInterval(messageInterval);
                
                // Attendre un peu pour que l'utilisateur voie le dernier message
                setTimeout(() => {
                    if (loaderOverlay) {
                        loaderOverlay.classList.add('hidden');
                    }
                }, 300);
            });
            
            // Fallback: cacher le loader après 15 secondes maximum (au cas où)
            setTimeout(() => {
                clearInterval(messageInterval);
                if (loaderOverlay && !loaderOverlay.classList.contains('hidden')) {
                    loaderOverlay.classList.add('hidden');
                }
            }, 15000);
        })();
    </script>
</body>
</html>
