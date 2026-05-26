<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy" content="frame-ancestors *">
    <title>Détails - {{ $property['StreetNumberStart'] ?? '' }} {{ $property['StreetShortName'] ?? '' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            padding: 15px;
        }

        .main-layout {
            max-width: 1600px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 15px;
            height: calc(100vh - 80px);
        }

        .container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            margin-bottom: 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            font-size: 14px;
            transition: all 0.3s;
        }

        .back-button:hover {
            background: #f5f5f5;
            border-color: #e31c23;
            color: #e31c23;
        }

        .left-sidebar {
            display: flex;
            flex-direction: column;
            gap: 15px;
            height: fit-content;
        }

        .property-card-details {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
        }

        .image-wrapper {
            position: relative;
        }

        .property-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .property-info-section {
            padding: 15px;
        }

        .property-info-section h1 {
            font-size: 14px;
            color: #333;
            margin-bottom: 10px;
            line-height: 1.3;
        }

        .price-type-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .price {
            font-size: 16px;
            color: #e31c23;
            font-weight: bold;
        }

        .type-badge {
            font-size: 11px;
            color: #666;
            background: #f5f5f5;
            padding: 3px 8px;
            border-radius: 12px;
        }

        .mls-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: white;
            color: #333;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            z-index: 10;
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

        .property-features {
            display: flex;
            flex-direction: row;
            justify-content: space-around;
            gap: 8px;
            padding: 12px 0;
            border-top: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
            margin: 12px 0;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #333;
            font-size: 13px;
        }

        .feature-item svg {
            width: 18px;
            height: 18px;
            fill: #666;
        }

        .listing-link-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            margin-top: 12px;
            padding: 10px 14px;
            background: #e31c23;
            color: white;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .listing-link-button:hover {
            background: #c71a1f;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(227, 28, 35, 0.2);
        }

        .listing-link-button svg {
            width: 16px;
            height: 16px;
            fill: currentColor;
        }

        .info-section {
            background: white;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
        }

        .info-section h3 {
            font-size: 14px;
            color: #333;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #666;
        }

        .info-value {
            color: #333;
            font-weight: 500;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .right-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.1);
            /* Allow inner dropdowns to overflow */
            overflow: visible;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .tabs-container {
            border-bottom: 2px solid #e0e0e0;
            padding: 0 20px;
        }

        .tabs {
            display: flex;
            gap: 0;
        }

        .tab {
            padding: 15px 20px;
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 15px;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            position: relative;
            top: 2px;
        }

        .tab:hover {
            color: #333;
        }

        .tab.active {
            color: #e31c23;
            border-bottom-color: #e31c23;
            font-weight: 600;
        }

        .tab-content {
            display: none;
            padding: 20px;
            height: calc(100vh - 200px);
            overflow-y: visible;
        }

        .tab-content.active {
            display: block;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .section-title {
            font-size: 18px;
            color: #333;
            font-weight: 600;
        }

        .add-button {
            padding: 8px 16px;
            background: #e31c23;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
        }

        .add-button:hover {
            background: #c71a1f;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(227, 28, 35, 0.3);
        }

        .search-box {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .search-box:focus {
            outline: none;
            border-color: #e31c23;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            display: block;
            border-radius: 8px;
            overflow: hidden;
        }

        thead {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            display: table;
            width: 100%;
            table-layout: fixed;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        tbody {
            display: block;
            max-height: 420px;
            overflow-y: auto;
            width: 100%;
            scroll-behavior: smooth;
        }

        /* Subtle, modern scrollbar */
        tbody::-webkit-scrollbar { width: 8px; }
        tbody::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        tbody::-webkit-scrollbar-thumb { background: #c0c0c0; border-radius: 4px; }
        tbody::-webkit-scrollbar-thumb:hover { background: #a0a0a0; }

        tbody tr {
            display: table;
            width: 100%;
            table-layout: fixed;
            transition: all 0.2s ease;
        }

        th {
            padding: 10px 12px;
            text-align: left;
            font-size: 13px;
            color: #666;
            font-weight: 600;
            border-bottom: 2px solid #e0e0e0;
        }

        th:nth-child(1) { width: 20%; }
        th:nth-child(2) { width: 28%; }
        th:nth-child(3) { width: 22%; }
        th:nth-child(4) { width: 22%; }
        th:nth-child(5) { width: 8%; text-align: center; }

        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
            color: #333;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        td:nth-child(1) { width: 20%; }
        td:nth-child(2) { width: 28%; }
        td:nth-child(3) { width: 22%; }
        td:nth-child(4) { width: 22%; }
        td:nth-child(5) { width: 8%; text-align: center; }

        tbody tr:hover {
            background: #f9f9f9;
        }

        .role-dropdown {
            padding: 6px 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
            color: #333;
            background: white;
            cursor: pointer;
        }

        .role-dropdown:focus {
            outline: none;
            border-color: #e31c23;
        }

        /* Custom role selector */
        .custom-role-select {
            position: relative;
            width: 100%;
        }

        .role-select-button {
            width: 100%;
            padding: 8px 32px 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
            color: #333;
            background: white;
            cursor: pointer;
            text-align: left;
            transition: all 0.3s;
            position: relative;
        }

        .role-select-button:hover {
            border-color: #e31c23;
            background: #fff5f5;
        }

        .role-select-button.active {
            border-color: #e31c23;
            box-shadow: 0 0 0 3px rgba(227, 28, 35, 0.1);
        }

        .role-select-button::after {
            content: '▼';
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 10px;
            color: #666;
            transition: transform 0.3s;
        }

        .role-select-button.active::after {
            transform: translateY(-50%) rotate(180deg);
        }

        .role-options-dropdown {
            display: none;
            position: fixed;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            margin-top: 4px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 2400;
            animation: slideDown 0.2s ease;
            min-width: 200px;
        }

        .role-options-dropdown.show {
            display: block;
        }

        .role-option-item {
            padding: 10px 12px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 13px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .role-option-item:hover {
            background: #f5f5f5;
        }

        .role-option-item.selected {
            background: #fff5f5;
            color: #e31c23;
            font-weight: 600;
        }

        .role-option-item .emoji {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .empty-state svg {
            width: 60px;
            height: 60px;
            fill: #ddd;
            margin-bottom: 15px;
        }

        .empty-state h3 {
            font-size: 16px;
            color: #666;
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 13px;
            color: #999;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .badge-warning {
            background: #fff3e0;
            color: #ef6c00;
        }

        .badge-info {
            background: #e3f2fd;
            color: #1976d2;
        }

        .action-menu {
            position: relative;
        }

        .action-button {
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 5px 10px;
            font-size: 20px;
            color: #666;
            transition: all 0.3s;
            border-radius: 4px;
        }

        .action-button:hover {
            background: #f0f0f0;
            color: #333;
        }

        .dropdown-menu {
            display: none;
            /* Fixed so it's not clipped by any overflow */
            position: fixed;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 180px;
            z-index: 2400;
            overflow: hidden;
            animation: slideDown 0.2s ease;
        }

        .dropdown-menu.show {
            display: block;
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

        .dropdown-item {
            padding: 12px 16px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #333;
            border: none;
            background: white;
            width: 100%;
            text-align: left;
        }

        .dropdown-item:hover {
            background: #f5f5f5;
        }

        .dropdown-item.delete {
            color: #e31c23;
        }

        .dropdown-item.delete:hover {
            background: #fff5f5;
        }

        .dropdown-item svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        /* Modal de confirmation personnalisé */
        .confirmation-modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            animation: fadeIn 0.2s;
        }

        .confirmation-modal.show {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .confirmation-content {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 440px;
            overflow: hidden;
            animation: scaleIn 0.3s ease;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        @keyframes scaleIn {
            from {
                transform: scale(0.9);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .confirmation-header {
            padding: 24px 24px 16px 24px;
            text-align: center;
        }

        .confirmation-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            background: linear-gradient(135deg, #ff6b6b 0%, #e31c23 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 0.5s ease;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .confirmation-icon svg {
            width: 32px;
            height: 32px;
            fill: white;
        }

        .confirmation-header h3 {
            font-size: 20px;
            color: #333;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .confirmation-body {
            padding: 0 24px 24px 24px;
            text-align: center;
        }

        .confirmation-body p {
            font-size: 15px;
            color: #666;
            line-height: 1.5;
        }

        .confirmation-footer {
            padding: 16px 24px 24px 24px;
            display: flex;
            gap: 12px;
        }

        .btn-confirmation-cancel {
            flex: 1;
            padding: 12px 24px;
            background: #f5f5f5;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }

        .btn-confirmation-cancel:hover {
            background: #e0e0e0;
            transform: translateY(-1px);
        }

        .btn-confirmation-confirm {
            flex: 1;
            padding: 12px 24px;
            background: linear-gradient(135deg, #ff6b6b 0%, #e31c23 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(227, 28, 35, 0.3);
        }

        .btn-confirmation-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(227, 28, 35, 0.4);
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }

        .modal.show {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            animation: slideIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 18px;
            color: #333;
            font-weight: 600;
        }

        .modal-header-title-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-refresh-header {
            padding: 6px 12px;
            background: #e31c23;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }

        .btn-refresh-header:hover {
            background: #c71a1f;
        }

        .close-modal {
            background: transparent;
            border: none;
            font-size: 28px;
            color: #666;
            cursor: pointer;
            line-height: 1;
            padding: 0;
            width: 30px;
            height: 30px;
        }

        .close-modal:hover {
            color: #e31c23;
        }

        .modal-body {
            padding: 20px;
            overflow-y: auto;
            flex: 1;
        }

        .modal-search {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .modal-search:focus {
            outline: none;
            border-color: #e31c23;
        }

        .contact-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .contact-item {
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .contact-item:hover {
            border-color: #e31c23;
            background: #fff5f5;
        }

        .contact-item.selected {
            border-color: #e31c23;
            background: #fff5f5;
        }

        .contact-name {
            font-size: 15px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .contact-details {
            font-size: 13px;
            color: #666;
        }

        .validation-message {
            font-size: 12px;
            color: #e31c23;
            margin-top: -8px;
            margin-bottom: 10px;
            display: none;
        }

        .modal-pagination {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .pagination-btn {
            padding: 6px 10px;
            border: 1px solid #ddd;
            background: #fff;
            color: #333;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
        }

        .pagination-btn:hover {
            border-color: #e31c23;
            color: #e31c23;
        }

        .pagination-btn.active {
            background: #e31c23;
            border-color: #e31c23;
            color: #fff;
        }

        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .role-selector {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .role-selector label {
            display: block;
            font-size: 14px;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .role-selector select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            color: #333;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-cancel {
            padding: 10px 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            color: #333;
            transition: all 0.3s;
        }

        .btn-cancel:hover {
            background: #f5f5f5;
        }

        .btn-confirm {
            padding: 10px 20px;
            background: #e31c23;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-confirm:hover {
            background: #c71a1f;
        }

        .btn-confirm:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .loading::after {
            content: '...';
            animation: dots 1.5s infinite;
        }

        @keyframes dots {
            0%, 20% { content: '.'; }
            40% { content: '..'; }
            60%, 100% { content: '...'; }
        }
    </style>
</head>
<body>
    <a href="{{ route('centris.properties', ['locationId' => $locationId]) }}" class="back-button">
        ← Retour
    </a>

    <div class="main-layout">
        <!-- Sidebar gauche avec info de la propriété -->
        <div class="left-sidebar">
            <div class="property-card-details">
                <div class="image-wrapper">
                    @if(isset($property['Media']) && count($property['Media']) > 0)
                        <img src="{{ $property['Media'][0]['MediaURL'] ?? '' }}" alt="Propriété" class="property-image">
                    @else
                        <img src="https://via.placeholder.com/320x200?text=Aucune+Image" alt="Aucune image" class="property-image">
                    @endif
                    <div class="mls-badge">MLS: {{ $property['ListingId'] ?? '' }}</div>
                    @php
                        $status = strtolower($property['MlsStatus'] ?? 'active');
                        $statusClass = 'status-active-overlay';
                        $statusText = 'En vigueur';
                        
                        if (in_array($status, ['sold', 'vendue', 'vendu'])) {
                            $statusClass = 'status-sold';
                            $statusText = 'Vendu';
                        } elseif (in_array($status, ['pending', 'en attente'])) {
                            $statusClass = 'status-pending';
                            $statusText = 'En attente';
                        } elseif ($status === 'active') {
                            $statusText = 'En vigueur';
                        }
                    @endphp
                    <div class="status-badge-overlay {{ $statusClass }}">{{ $statusText }}</div>
                </div>
                
                <div class="property-info-section">
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
                    <h1>{{ $fullAddress }}</h1>
                    
                    @php
                        $price = $property['ListPrice'] ?? $property['RentPrice'] ?? 0;
                        $isRent = (!isset($property['ListPrice']) || $property['ListPrice'] == 0) && isset($property['RentPrice']);
                    @endphp
                    <div class="price-type-row">
                        <div class="price">{{ number_format($price, 0, ',', ' ') }} ${{ $isRent ? '/mois' : '' }}</div>
                        <span class="type-badge">{{ $property['PropertySubType'] ?? $property['PropertyType'] ?? 'Propriété' }}</span>
                    </div>
                    
                    <div class="property-features">
                        <div class="feature-item">
                            <img src="{{ asset('img/sleep.svg') }}" alt="Chambres" style="width: 18px; height: 18px;">
                            <span>{{ $property['BedroomsTotal'] ?? 0 }}</span>
                        </div>
                        
                        <div class="feature-item">
                            <img src="{{ asset('img/Bathrooms.svg') }}" alt="Salles de bain" style="width: 18px; height: 18px;">
                            <span>{{ ($property['BathroomsFull'] ?? 0) + ($property['BathroomsPartial'] ?? 0) }}</span>
                        </div>
                        
                        <div class="feature-item">
                            <img src="{{ asset('img/living area.svg') }}" alt="Superficie" style="width: 18px; height: 18px;">
                            <span>{{ $property['LivingArea'] ?? 0 }}</span>
                        </div>
                    </div>

                    @if(!empty($property['ListingURL']))
                        <a href="{{ $property['ListingURL'] }}" target="_blank" rel="noopener noreferrer" class="listing-link-button">
                            <svg viewBox="0 0 24 24">
                                <path d="M14,3V5H17.59L7.76,14.83L9.17,16.24L19,6.41V10H21V3M19,19H5V5H12V3H5C3.89,3 3,3.9 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V12H19V19Z"/>
                            </svg>
                            Voir l'inscription
                        </a>
                    @endif
                    
                    {{-- Afficher le nom du member (agent) --}}
                    {{-- DEBUG: --}}
                    <!-- memberName value: "{{ $memberName ?? 'NULL' }}" -->
                    <!-- ListAgentKey: "{{ $property['ListAgentKey'] ?? 'NOT FOUND' }}" -->
                    
                    @if(!empty($memberName))
                        <div style="margin-top: 12px; padding: 10px 12px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 6px; border-left: 3px solid #e31c23;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span style="font-size: 12px; color: #595858ff; font-weight: 600;letter-spacing: 0.5px;">Courtier :</span>
                                <span style="font-size: 14px; color: #333; font-weight: 500;">{{ $memberName }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="info-section">
                <h3>Informations principales</h3>
                <div class="info-row">
                    <span class="info-label">Date d'inscription</span>
                    <span class="info-value">{{ isset($property['OnMarketTimestamp']) ? date('d/m/Y', strtotime($property['OnMarketTimestamp'])) : '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Année de construction</span>
                    <span class="info-value">{{ $property['YearBuilt'] ?? '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Contenu principal à droite -->
        <div class="right-content">
            <!-- Onglets -->
        <div class="tabs-container">
            <div class="tabs">
                <button class="tab active" onclick="switchTab(event, 'persons')">Personnes impliquées</button>
                <button class="tab" onclick="switchTab(event, 'opportunities')">Opportunités liées</button>
                <button class="tab" onclick="switchTab(event, 'centris-submissions')">Soumission centris</button>
            </div>
        </div>

        <!-- Contenu Personnes -->
        <div id="persons-tab" class="tab-content active">
            <div class="section-header">
                <h2 class="section-title">Personnes impliquées</h2>
                <button class="add-button" onclick="openAddPersonModal()">
                    + Ajouter
                </button>
            </div>

            <input type="text" class="search-box" placeholder="Rechercher dans le tableau pour une personne impliquée" id="search-persons">

            @if(count($persons) > 0)
                <table id="personsTable">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Implication</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($persons as $person)
                            <tr data-person-id="{{ $person['id'] ?? '' }}">
                                <td>{{ $person['name'] ?? '' }}</td>
                                <td>{{ $person['email'] ?? '-' }}</td>
                                <td>{{ $person['phone'] ?? '-' }}</td>
                                <td>
                                    <div class="custom-role-select">
                                        <button type="button" class="role-select-button" id="role-select-{{ $person['id'] ?? 0 }}" onclick="toggleRoleDropdown(event, '{{ $person['id'] ?? 0 }}')">
                                            @php
                                                $roleValue = $person['role'] ?? $person['implication'] ?? '';
                                                $roles = [
                                                    '' => ['label' => 'Sélectionner un rôle', 'emoji' => ''],
                                                    'proprietaire' => ['label' => 'Propriétaire', 'emoji' => '💼'],
                                                    'acheteur_potentiel' => ['label' => 'Acheteur potentiel', 'emoji' => '🕵️'],
                                                    'acheteur_final' => ['label' => 'Acheteur final', 'emoji' => '🏡'],
                                                    'acheteur_non_interesse' => ['label' => 'Acheteur non intéressé', 'emoji' => '🚫'],
                                                    'courtier_collaborateur' => ['label' => 'Courtier collaborateur', 'emoji' => '🤝'],
                                                    'adjoint' => ['label' => 'Adjoint·e', 'emoji' => '🧑'],
                                                    'courtier_hypothecaire' => ['label' => 'Courtier hypothécaire', 'emoji' => '🏦'],
                                                    'notaire' => ['label' => 'Notaire', 'emoji' => '📜'],
                                                    'inspecteur' => ['label' => 'Inspecteur en bâtiment', 'emoji' => '🏚️'],
                                                    'architecte' => ['label' => 'Architecte', 'emoji' => '🏛️'],
                                                    'arpenteur' => ['label' => 'Arpenteur-géomètre', 'emoji' => '🧱'],
                                                    'designer' => ['label' => 'Designer d\'intérieur', 'emoji' => '🎨'],
                                                    'entrepreneur' => ['label' => 'Entrepreneur en rénovation', 'emoji' => '🛠️'],
                                                    'evaluateur' => ['label' => 'Évaluateur', 'emoji' => '📈'],
                                                    'gestionnaire_copropriete' => ['label' => 'Gestionnaire de copropriété', 'emoji' => '🏢'],
                                                    'gestionnaire_propriete' => ['label' => 'Gestionnaire de propriété', 'emoji' => '🏢'],
                                                    'municipalite' => ['label' => 'Représentant de la municipalité', 'emoji' => '🏘️'],
                                                    'photographe' => ['label' => 'Photographe / Vidéaste', 'emoji' => '📸'],
                                                    'paysagiste' => ['label' => 'Paysagiste', 'emoji' => '🌳'],
                                                    'demenagement' => ['label' => 'Service de déménagement', 'emoji' => '🚚'],
                                                    'nettoyage' => ['label' => 'Service de nettoyage', 'emoji' => '🧹'],
                                                    'assurance' => ['label' => 'Agent d\'assurance', 'emoji' => '🛡️'],
                                                    'avocat' => ['label' => 'Avocat', 'emoji' => '⚖️'],
                                                    'autre' => ['label' => 'Autre', 'emoji' => '🗂️']
                                                ];
                                                $currentRole = $roles[$roleValue] ?? $roles[''];
                                                $displayText = $currentRole['emoji'] ? "{$currentRole['emoji']} {$currentRole['label']}" : $currentRole['label'];
                                            @endphp
                                            {{ $displayText }}
                                        </button>
                                        <div class="role-options-dropdown" id="role-dropdown-{{ $person['id'] ?? 0 }}">
                                            @foreach($roles as $value => $role)
                                                <div class="role-option-item {{ $roleValue === $value ? 'selected' : '' }}" onclick="selectRole(event, '{{ $person['id'] ?? 0 }}', '{{ $value }}')">
                                                    @if($role['emoji'])
                                                        <span class="emoji">{{ $role['emoji'] }}</span>
                                                    @else
                                                        <span class="emoji"></span>
                                                    @endif
                                                    <span>{{ $role['label'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                                <td class="action-menu">
                                    <button class="action-button" onclick="toggleDropdown(event, {{ $person['id'] ?? 0 }}, 'person')">⋮</button>
                                    <div class="dropdown-menu" id="dropdown-person-{{ $person['id'] ?? 0 }}">
                                        <button class="dropdown-item" onclick="viewPerson(event, {{ $person['id'] ?? 0 }})">
                                            <svg viewBox="0 0 24 24"><path d="M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z"/></svg>
                                            Voir
                                        </button>
                                        <button class="dropdown-item delete" onclick="confirmRemovePerson(event, {{ $person['id'] ?? 0 }})">
                                            <svg viewBox="0 0 24 24"><path d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z"/></svg>
                                            Supprimer
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <svg viewBox="0 0 24 24">
                        <path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/>
                    </svg>
                    <h3>Aucune personne impliquée</h3>
                    <p>Cliquez sur "Ajouter" pour associer une personne à cette propriété</p>
                </div>
            @endif
        </div>

        <!-- Contenu Opportunités -->
        <div id="opportunities-tab" class="tab-content">
            <div class="section-header">
                <h2 class="section-title">Opportunités liées</h2>
                <button class="add-button" onclick="openAddOpportunityModal()">
                    + Ajouter
                </button>
            </div>

            <input type="text" class="search-box" placeholder="Rechercher dans le tableau pour une opportunité liée" id="search-opportunities">

            @if(count($opportunities) > 0)
                <table id="opportunitiesTable">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Pipeline</th>
                            <th>Étape</th>
                            <th>Source</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($opportunities as $opportunity)
                            <tr data-opportunity-id="{{ $opportunity['id'] ?? '' }}">
                                <td>{{ $opportunity['name'] ?? '' }}</td>
                                <td>{{ $opportunity['pipelineId'] ?? '-' }}</td>
                                <td>{{ $opportunity['pipelineStageId'] ?? '-' }}</td>
                                <td>{{ $opportunity['source'] ?? '-' }}</td>
                                <td class="action-menu">
                                    <button class="action-button" onclick="toggleDropdown(event, {{ $opportunity['id'] ?? 0 }}, 'opportunity')">⋮</button>
                                    <div class="dropdown-menu" id="dropdown-opportunity-{{ $opportunity['id'] ?? 0 }}">
                                        <button class="dropdown-item" onclick="viewOpportunity(event, {{ $opportunity['id'] ?? 0 }})">
                                            <svg viewBox="0 0 24 24"><path d="M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z"/></svg>
                                            Voir
                                        </button>
                                        <button class="dropdown-item delete" onclick="confirmRemoveOpportunity(event, {{ $opportunity['id'] ?? 0 }})">
                                            <svg viewBox="0 0 24 24"><path d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z"/></svg>
                                            Supprimer
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <svg viewBox="0 0 24 24">
                        <path d="M21,16.5C21,16.88 20.79,17.21 20.47,17.38L12.57,21.82C12.41,21.94 12.21,22 12,22C11.79,22 11.59,21.94 11.43,21.82L3.53,17.38C3.21,17.21 3,16.88 3,16.5V7.5C3,7.12 3.21,6.79 3.53,6.62L11.43,2.18C11.59,2.06 11.79,2 12,2C12.21,2 12.41,2.06 12.57,2.18L20.47,6.62C20.79,6.79 21,7.12 21,7.5V16.5M12,4.15L6.04,7.5L12,10.85L17.96,7.5L12,4.15M5,15.91L11,19.29V12.58L5,9.21V15.91M19,15.91V9.21L13,12.58V19.29L19,15.91Z"/>
                    </svg>
                    <h3>Aucune opportunité liée</h3>
                    <p>Cliquez sur "Ajouter" pour créer une opportunité pour cette propriété</p>
                </div>
            @endif
        </div>

        <!-- Contenu Soumissions Centris -->
        <div id="centris-submissions-tab" class="tab-content">
            <div class="section-header">
                <h2 class="section-title">Soumission centris</h2>
            </div>

            <input type="text" class="search-box" placeholder="Rechercher dans les soumissions Centris" id="search-centris-submissions">

            @if(count($centrisSubmissions ?? []) > 0)
                <table id="centrisSubmissionsTable">
                    <thead>
                        <tr>
                            <th>Prénom</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($centrisSubmissions as $submission)
                            <tr data-submission-id="{{ $submission['id'] ?? '' }}">
                                <td>{{ $submission['firstName'] ?? '-' }}</td>
                                <td>{{ $submission['lastName'] ?? '-' }}</td>
                                <td>{{ $submission['email'] ?? '-' }}</td>
                                <td>{{ $submission['phone'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="empty-state">
                    <svg viewBox="0 0 24 24">
                        <path d="M19,3H5C3.9,3 3,3.9 3,5V19C3,20.1 3.9,21 5,21H19C20.1,21 21,20.1 21,19V5C21,3.9 20.1,3 19,3M19,19H5V5H19V19M7,7H17V9H7V7M7,11H17V13H7V11M7,15H13V17H7V15Z"/>
                    </svg>
                    <h3>Aucune soumission Centris</h3>
                    <p>Les soumissions reçues de Centris pour cette fiche apparaîtront ici</p>
                </div>
            @endif
        </div>
        </div>
    </div>

    <!-- Modal pour ajouter une personne -->
    <div id="addPersonModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ajouter une personne impliquée</h3>
                <div class="modal-header-actions">
                    <button type="button" class="btn-refresh-header" onclick="refreshContactsList()">Rafraîchir la liste</button>
                    <button class="close-modal" onclick="closeAddPersonModal()">&times;</button>
                </div>
            </div>
            <div class="modal-body">
                <input type="text" class="modal-search" id="searchPersonsModal" placeholder="Rechercher un contact (au moins 3 lettres)...">
                <div id="contactsSearchValidation" class="validation-message">Veuillez entrer au moins 3 lettres pour rechercher.</div>
                <div id="contactsPagination" class="modal-pagination"></div>
                <div id="contactsFoundSummary" class="contact-details">Aucun contact trouvé</div>
                <div id="contactListContainer">
                    <div class="loading">Chargement des contacts</div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeAddPersonModal()">Annuler</button>
                <button class="btn-confirm" id="confirmAddPerson" onclick="confirmAddPerson()" disabled>Ajouter</button>
            </div>
        </div>
    </div>

    <!-- Modal pour ajouter une opportunité -->
    <div id="addOpportunityModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Ajouter une opportunité liée</h3>
                <div class="modal-header-actions">
                    <button type="button" class="btn-refresh-header" onclick="refreshOpportunitiesList()">Rafraîchir la liste</button>
                    <button class="close-modal" onclick="closeAddOpportunityModal()">&times;</button>
                </div>
            </div>
            <div class="modal-body">
                <input type="text" class="modal-search" id="searchOpportunitiesModal" placeholder="Rechercher une opportunité...">
                <div id="opportunitiesFoundCount" class="contact-details">0 opportunité trouvée</div>
                <div id="opportunityListContainer">
                    <div class="loading">Chargement des opportunités</div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeAddOpportunityModal()">Annuler</button>
                <button class="btn-confirm" id="confirmAddOpportunity" onclick="confirmAddOpportunity()" disabled>Ajouter</button>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div id="confirmationModal" class="confirmation-modal">
        <div class="confirmation-content">
            <div class="confirmation-header">
                <div class="confirmation-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z"/>
                    </svg>
                </div>
                <h3>Confirmer la suppression</h3>
            </div>
            <div class="confirmation-body">
                <p id="confirmationMessage">Êtes-vous sûr de vouloir supprimer cet élément ?</p>
            </div>
            <div class="confirmation-footer">
                <button class="btn-confirmation-cancel" onclick="closeConfirmationModal()">Annuler</button>
                <button class="btn-confirmation-confirm" id="confirmDeleteBtn">Supprimer</button>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        const idLocation = '{{ $idLocation }}';
        const propertyListingId = '{{ $property["ListingId"] ?? "" }}';
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        
        let selectedContact = null;
        let allContacts = [];
        let contactsCache = {}; // Cache des contacts GHL par page
        let contactsLastLoaded = 0; // Timestamp du dernier chargement
        const CACHE_DURATION = 30 * 60 * 1000; // 30 minutes en millisecondes
        let contactsCurrentPage = 1;
        let contactsTotalPages = 1;
        let contactsHasNextPage = false;
        let contactsTotalFound = 0;
        let contactsSearchTerm = '';
        let contactsSearchTimeout = null;
        let contactsRequestController = null;
        const CONTACT_SEARCH_MIN_LENGTH = 3;
        let currentPersons = @json($persons ?? []);
        let selectedOpportunity = null;
        let allOpportunities = [];
        let opportunitiesCache = null; // Cache pour les opportunités GHL
        let opportunitiesLastLoaded = 0;
        let currentOpportunities = @json($opportunities ?? []);
        let currentCentrisSubmissions = @json($centrisSubmissions ?? []);

        // Liste des rôles avec emojis
        const roleOptions = [
            { value: '', label: 'Sélectionner un rôle', emoji: '' },
            { value: 'proprietaire', label: 'Propriétaire', emoji: '💼' },
            { value: 'acheteur_potentiel', label: 'Acheteur potentiel', emoji: '🕵️' },
            { value: 'acheteur_final', label: 'Acheteur final', emoji: '🏡' },
            { value: 'acheteur_non_interesse', label: 'Acheteur non intéressé', emoji: '🚫' },
            { value: 'courtier_collaborateur', label: 'Courtier collaborateur', emoji: '🤝' },
            { value: 'adjoint', label: 'Adjoint·e', emoji: '🧑' },
            { value: 'courtier_hypothecaire', label: 'Courtier hypothécaire', emoji: '🏦' },
            { value: 'notaire', label: 'Notaire', emoji: '📜' },
            { value: 'inspecteur', label: 'Inspecteur en bâtiment', emoji: '🏚️' },
            { value: 'architecte', label: 'Architecte', emoji: '🏛️' },
            { value: 'arpenteur', label: 'Arpenteur-géomètre', emoji: '🧱' },
            { value: 'designer', label: 'Designer d\'intérieur', emoji: '🎨' },
            { value: 'entrepreneur', label: 'Entrepreneur en rénovation', emoji: '🛠️' },
            { value: 'evaluateur', label: 'Évaluateur', emoji: '📈' },
            { value: 'gestionnaire_copropriete', label: 'Gestionnaire de copropriété', emoji: '🏢' },
            { value: 'gestionnaire_propriete', label: 'Gestionnaire de propriété', emoji: '🏢' },
            { value: 'municipalite', label: 'Représentant de la municipalité', emoji: '🏘️' },
            { value: 'photographe', label: 'Photographe / Vidéaste', emoji: '📸' },
            { value: 'paysagiste', label: 'Paysagiste', emoji: '🌳' },
            { value: 'demenagement', label: 'Service de déménagement', emoji: '🚚' },
            { value: 'nettoyage', label: 'Service de nettoyage', emoji: '🧹' },
            { value: 'assurance', label: 'Agent d\'assurance', emoji: '🛡️' },
            { value: 'avocat', label: 'Avocat', emoji: '⚖️' },
            { value: 'autre', label: 'Autre', emoji: '🗂️' }
        ];

        // Fonction pour traduire les statuts en français
        function translateStatus(status) {
            const translations = {
                'open': 'Ouvert',
                'won': 'Gagné',
                'abandoned': 'Abandonné',
                'lost': 'Perdu'
            };
            return translations[status?.toLowerCase()] || status || 'N/A';
        }

        // Variables pour le modal de confirmation
        let pendingDeleteAction = null;

        // Fonction pour ouvrir le modal de confirmation
        function openConfirmationModal(message, onConfirm) {
            document.getElementById('confirmationMessage').textContent = message;
            document.getElementById('confirmationModal').classList.add('show');
            
            // Stocker l'action de confirmation
            pendingDeleteAction = onConfirm;
            
            // Attacher l'événement au bouton de confirmation
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            confirmBtn.onclick = function() {
                if (pendingDeleteAction) {
                    pendingDeleteAction();
                }
                closeConfirmationModal();
            };
        }

        // Fonction pour fermer le modal de confirmation
        function closeConfirmationModal() {
            document.getElementById('confirmationModal').classList.remove('show');
            pendingDeleteAction = null;
        }

        // Fonction pour gérer le menu déroulant (positionné en fixed pour éviter d'être caché)
        function toggleDropdown(event, id, type) {
            event.stopPropagation();
            const triggerBtn = event.currentTarget;
            const dropdown = document.getElementById(`dropdown-${type}-${id}`);

            // Fermer tous les autres dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                if (menu !== dropdown) {
                    menu.classList.remove('show');
                }
            });

            const willOpen = !dropdown.classList.contains('show');
            dropdown.classList.toggle('show');

            if (willOpen) {
                // Calculer la position à l'écran du bouton (viewport coords)
                const rect = triggerBtn.getBoundingClientRect();
                // Rendre mesurable
                dropdown.style.visibility = 'hidden';
                dropdown.style.display = 'block';
                const ddWidth = dropdown.offsetWidth || 200;
                const ddHeight = dropdown.offsetHeight || 120;
                // Alignement par défaut: droite du bouton
                let left = rect.right - ddWidth;
                let top = rect.bottom + 6;
                // Garder dans le viewport
                left = Math.max(8, Math.min(left, window.innerWidth - ddWidth - 8));
                top = Math.max(8, Math.min(top, window.innerHeight - ddHeight - 8));
                // Appliquer
                dropdown.style.left = `${left}px`;
                dropdown.style.top = `${top}px`;
                dropdown.style.visibility = 'visible';
                dropdown.style.display = '';
            }
        }

        // Fermer les dropdowns quand on clique ailleurs
        document.addEventListener('click', function(event) {
            const target = event.target;
            if (target && target.closest) {
                if (!target.closest('.action-menu')) {
                    document.querySelectorAll('.dropdown-menu').forEach(menu => {
                        menu.classList.remove('show');
                    });
                }
                
                // Fermer les role selectors si on clique ailleurs
                if (!target.closest('.custom-role-select')) {
                    document.querySelectorAll('.role-options-dropdown').forEach(dropdown => {
                        dropdown.classList.remove('show');
                    });
                    document.querySelectorAll('.role-select-button').forEach(btn => {
                        btn.classList.remove('active');
                    });
                }
            }
        });

        // Fermer les role dropdowns lors du scroll
        document.addEventListener('scroll', function(event) {
            // Ne pas fermer si on scroll dans le dropdown lui-même
            if (event.target.classList && event.target.classList.contains('role-options-dropdown')) {
                return;
            }
            
            // Fermer si on scroll dans le tbody
            const target = event.target;
            if (target && (target.tagName === 'TBODY' || (target.closest && target.closest('tbody')))) {
                document.querySelectorAll('.role-options-dropdown').forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
                document.querySelectorAll('.role-select-button').forEach(btn => {
                    btn.classList.remove('active');
                });
                // Fermer aussi les menus d'action
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        }, true);

        // Fermer les dropdowns lors d'un resize de la fenêtre (évite des positions invalides)
        window.addEventListener('resize', function() {
            document.querySelectorAll('.role-options-dropdown').forEach(dropdown => dropdown.classList.remove('show'));
            document.querySelectorAll('.role-select-button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.dropdown-menu').forEach(menu => menu.classList.remove('show'));
        });

        // Fonction pour créer un custom role selector
        function createRoleSelector(currentRole, personId) {
            const selectId = `role-select-${personId}`;
            const dropdownId = `role-dropdown-${personId}`;
            
            const currentOption = roleOptions.find(opt => opt.value === currentRole) || roleOptions[0];
            const displayText = currentOption.emoji ? `${currentOption.emoji} ${currentOption.label}` : currentOption.label;
            
            return `
                <div class="custom-role-select">
                    <button type="button" class="role-select-button" id="${selectId}" onclick="toggleRoleDropdown(event, '${personId}')">
                        ${displayText}
                    </button>
                    <div class="role-options-dropdown" id="${dropdownId}">
                        ${roleOptions.map(option => `
                            <div class="role-option-item ${option.value === currentRole ? 'selected' : ''}" 
                                 onclick="selectRole(event, '${personId}', '${option.value}')">
                                ${option.emoji ? `<span class="emoji">${option.emoji}</span>` : '<span class="emoji"></span>'}
                                <span>${option.label}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        // Toggle role dropdown
        function toggleRoleDropdown(event, personId) {
            event.stopPropagation();
            
            const selectId = `role-select-${personId}`;
            const dropdownId = `role-dropdown-${personId}`;
            
            // Fermer tous les autres dropdowns
            document.querySelectorAll('.role-options-dropdown').forEach(dropdown => {
                if (dropdown.id !== dropdownId) {
                    dropdown.classList.remove('show');
                }
            });
            document.querySelectorAll('.role-select-button').forEach(btn => {
                if (btn.id !== selectId) {
                    btn.classList.remove('active');
                }
            });
            
            // Toggle le dropdown actuel
            const dropdown = document.getElementById(dropdownId);
            const button = document.getElementById(selectId);
            
            const isOpening = !dropdown.classList.contains('show');
            
            dropdown.classList.toggle('show');
            button.classList.toggle('active');
            
            // Positionner le dropdown si on l'ouvre
            if (isOpening) {
                const buttonRect = button.getBoundingClientRect();
                // Préparer pour mesure et contraindre la largeur au bouton
                dropdown.style.visibility = 'hidden';
                dropdown.style.display = 'block';
                dropdown.style.width = `${buttonRect.width}px`;

                const ddWidth = dropdown.offsetWidth || buttonRect.width;
                const ddHeight = dropdown.offsetHeight || 200;
                const margin = 8;

                // Espace disponible
                const spaceBelow = window.innerHeight - buttonRect.bottom - margin;
                const spaceAbove = buttonRect.top - margin;

                // Horizontale: garder dans le viewport
                let left = Math.max(margin, Math.min(buttonRect.left, window.innerWidth - ddWidth - margin));

                // Choisir d'ouvrir au-dessus si pas assez de place en bas
                const openAbove = ddHeight > spaceBelow && spaceAbove > spaceBelow;
                let top;
                let maxHeight;
                if (openAbove) {
                    maxHeight = Math.max(160, Math.min(ddHeight, spaceAbove));
                    top = Math.max(margin, buttonRect.top - maxHeight - 4);
                } else {
                    maxHeight = Math.max(160, Math.min(ddHeight, spaceBelow));
                    top = Math.min(window.innerHeight - margin - maxHeight, buttonRect.bottom + 4);
                }

                dropdown.style.left = `${left}px`;
                dropdown.style.top = `${top}px`;
                dropdown.style.maxHeight = `${maxHeight}px`;

                // Afficher
                dropdown.style.visibility = 'visible';
                dropdown.style.display = '';
            }
        }

        // Sélectionner un rôle
        async function selectRole(event, personId, roleValue) {
            event.stopPropagation();
            
            const selectId = `role-select-${personId}`;
            const dropdownId = `role-dropdown-${personId}`;
            
            const selectedOption = roleOptions.find(opt => opt.value === roleValue) || roleOptions[0];
            const displayText = selectedOption.emoji ? `${selectedOption.emoji} ${selectedOption.label}` : selectedOption.label;
            
            // Mettre à jour l'affichage
            const button = document.getElementById(selectId);
            button.textContent = displayText;
            
            // Fermer le dropdown
            document.getElementById(dropdownId).classList.remove('show');
            button.classList.remove('active');
            
            // Mettre à jour la sélection
            document.querySelectorAll(`#${dropdownId} .role-option-item`).forEach(item => {
                item.classList.remove('selected');
            });
            
            // Vérifier que event.target a la méthode closest
            const targetElement = event.target;
            if (targetElement && targetElement.closest) {
                const roleItem = targetElement.closest('.role-option-item');
                if (roleItem) {
                    roleItem.classList.add('selected');
                }
            }
            
            // Sauvegarder dans la base de données
            await updatePersonRole(personId, roleValue);
        }

        // Mettre à jour le rôle dans la base de données
        async function updatePersonRole(personId, role) {
            try {
                const response = await fetch(`/api/properties/persons/${personId}/role`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        id_location: idLocation,
                        implication: role
                    })
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Erreur lors de la mise à jour');
                }

                // Mettre à jour dans currentPersons
                const person = currentPersons.find(p => p.id === personId);
                if (person) {
                    person.role = role;
                }

                console.log('Rôle mis à jour avec succès:', role);

            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de la mise à jour du rôle: ' + error.message);
            }
        }

        function switchTab(event, tabName) {
            // Retirer active de tous les onglets et contenus
            document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Activer l'onglet et le contenu sélectionnés
            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        }

        // Recherche dans les tableaux
        document.getElementById('search-persons')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#persons-tab tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        document.getElementById('search-opportunities')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#opportunities-tab tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        document.getElementById('search-centris-submissions')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#centris-submissions-tab tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Fonctions pour la modale d'ajout de personne
        async function openAddPersonModal() {
            const modal = document.getElementById('addPersonModal');
            modal.classList.add('show');
            contactsCurrentPage = 1;
            contactsSearchTerm = '';
            const validation = document.getElementById('contactsSearchValidation');
            if (validation) {
                validation.style.display = 'none';
            }
            const searchInput = document.getElementById('searchPersonsModal');
            if (searchInput) {
                searchInput.value = '';
            }
            
            // Charger les contacts depuis l'API GHL (avec cache)
            await loadGHLContacts(false, contactsCurrentPage);
        }

        function closeAddPersonModal() {
            const modal = document.getElementById('addPersonModal');
            modal.classList.remove('show');
            selectedContact = null;
            const confirmButton = document.getElementById('confirmAddPerson');
            confirmButton.disabled = true;
            confirmButton.textContent = 'Ajouter';
            // Réinitialiser la recherche
            document.getElementById('searchPersonsModal').value = '';
            contactsSearchTerm = '';
            const validation = document.getElementById('contactsSearchValidation');
            if (validation) {
                validation.style.display = 'none';
            }
            if (contactsSearchTimeout) {
                clearTimeout(contactsSearchTimeout);
                contactsSearchTimeout = null;
            }
            if (contactsRequestController) {
                contactsRequestController.abort();
                contactsRequestController = null;
            }
        }

        async function refreshContactsList() {
            contactsCache = {};
            contactsLastLoaded = 0;
            contactsCurrentPage = 1;
            contactsTotalFound = 0;
            contactsSearchTerm = (document.getElementById('searchPersonsModal')?.value || '').trim();
            selectedContact = null;
            const confirmButton = document.getElementById('confirmAddPerson');
            if (confirmButton) {
                confirmButton.disabled = true;
            }
            await loadGHLContacts(true, contactsCurrentPage);
        }

        async function loadGHLContacts(forceRefresh = false, page = 1) {
            const container = document.getElementById('contactListContainer');
            const safePage = Math.max(1, Number(page) || 1);
            contactsCurrentPage = safePage;
            const normalizedSearch = (contactsSearchTerm || '').trim().toLowerCase();
            const effectiveSearch = normalizedSearch.length >= CONTACT_SEARCH_MIN_LENGTH ? normalizedSearch : '';
            const cacheKey = `${safePage}::${effectiveSearch}`;
            
            // Vérifier si on a un cache valide
            const now = Date.now();
            if (!forceRefresh && contactsCache[cacheKey] && (now - contactsLastLoaded) < CACHE_DURATION) {
                console.log('Utilisation du cache des contacts');
                const cachedData = contactsCache[cacheKey];
                contactsTotalPages = cachedData.totalPages || contactsTotalPages;
                contactsHasNextPage = !!cachedData.hasNextPage;
                contactsTotalFound = Number(cachedData.total || 0);
                // Filtrer les contacts qui ne sont pas déjà dans currentPersons
                const availableContacts = (cachedData.contacts || []).filter(contact => {
                    return !currentPersons.some(p => p.contact_id === contact.id);
                });
                allContacts = availableContacts;
                displayContacts(allContacts);
                renderContactsPagination();
                return;
            }

            container.innerHTML = '<div class="loading">Chargement des contacts...</div>';

            try {
                // Appeler notre API Laravel au lieu de l'API GHL directement
                let url = `{{ route("api.ghl.contacts") }}?locationId={{ $locationId }}&page=${safePage}`;
                if (effectiveSearch !== '') {
                    url += `&search=${encodeURIComponent(effectiveSearch)}`;
                }
                if (forceRefresh) {
                    url += '&force_refresh=1';
                }

                if (contactsRequestController) {
                    contactsRequestController.abort();
                }
                const requestController = new AbortController();
                contactsRequestController = requestController;

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    },
                    signal: requestController.signal
                });

                if (!response.ok) {
                    throw new Error('Erreur lors du chargement des contacts');
                }

                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Erreur inconnue');
                }

                contactsTotalPages = Math.max(1, Number(data.totalPages) || 1);
                contactsHasNextPage = !!data.hasNextPage;
                contactsTotalFound = Number(data.total || 0);

                // Mettre en cache la page
                contactsCache[cacheKey] = {
                    contacts: data.contacts || [],
                    totalPages: contactsTotalPages,
                    hasNextPage: contactsHasNextPage,
                    total: contactsTotalFound
                };
                contactsLastLoaded = Date.now();

                // Filtrer les contacts qui ne sont pas déjà dans currentPersons
                const availableContacts = (data.contacts || []).filter(contact => {
                    return !currentPersons.some(p => p.contact_id === contact.id);
                });

                allContacts = availableContacts;
                renderContactsPagination();

                if (allContacts.length === 0) {
                    container.innerHTML = '<div class="empty-state"><p>Aucun nouveau contact disponible</p></div>';
                    return;
                }

                // Afficher la liste des contacts
                displayContacts(allContacts);

            } catch (error) {
                if (error.name === 'AbortError') {
                    return;
                }
                console.error('Erreur:', error);
                container.innerHTML = '<div class="empty-state"><p style="color: #e31c23;">Erreur lors du chargement des contacts</p></div>';
            } finally {
                if (contactsRequestController === requestController) {
                    contactsRequestController = null;
                }
            }
        }

        function renderContactsPagination() {
            const pagination = document.getElementById('contactsPagination');
            if (!pagination) {
                return;
            }

            const totalPagesToShow = Math.max(1, contactsTotalPages);
            const startPage = Math.max(1, contactsCurrentPage - 2);
            const endPage = Math.min(totalPagesToShow, contactsCurrentPage + 2);

            let html = '';
            html += `<button type="button" class="pagination-btn" onclick="goToContactsPage(${contactsCurrentPage - 1})" ${contactsCurrentPage <= 1 ? 'disabled' : ''}>Précédent</button>`;

            for (let pageNum = startPage; pageNum <= endPage; pageNum++) {
                html += `<button type="button" class="pagination-btn ${pageNum === contactsCurrentPage ? 'active' : ''}" onclick="goToContactsPage(${pageNum})">${pageNum}</button>`;
            }

            const canGoNext = contactsCurrentPage < totalPagesToShow || contactsHasNextPage;
            html += `<button type="button" class="pagination-btn" onclick="goToContactsPage(${contactsCurrentPage + 1})" ${canGoNext ? '' : 'disabled'}>Suivant</button>`;

            pagination.innerHTML = html;
        }

        async function goToContactsPage(page) {
            const requestedPage = Math.max(1, Number(page) || 1);
            if (requestedPage === contactsCurrentPage) {
                return;
            }

            if (requestedPage > contactsTotalPages && !contactsHasNextPage) {
                return;
            }

            selectedContact = null;
            const confirmButton = document.getElementById('confirmAddPerson');
            if (confirmButton) {
                confirmButton.disabled = true;
            }

            await loadGHLContacts(false, requestedPage);
        }

        function displayContacts(contacts) {
            const container = document.getElementById('contactListContainer');
            const summaryElement = document.getElementById('contactsFoundSummary');
            const pageCount = contacts.length;
            const totalCount = contactsTotalFound > 0 ? contactsTotalFound : pageCount;

            if (summaryElement) {
                if (totalCount === 0) {
                    summaryElement.textContent = 'Aucun contact trouvé';
                } else {
                    summaryElement.textContent = `Total: ${totalCount} contact${totalCount > 1 ? 's' : ''} trouvé${totalCount > 1 ? 's' : ''}`;
                }
            }
            
            if (pageCount === 0) {
                container.innerHTML = '<div class="empty-state"><p>Aucun contact trouvé</p></div>';
                return;
            }

            container.innerHTML = '<div class="contact-list">' + 
                contacts.map(contact => `
                    <div class="contact-item" data-contact-id="${contact.id}" onclick="selectContact('${contact.id}')">
                        <div class="contact-name">${contact.name}</div>
                    </div>
                `).join('') +
            '</div>';
        }

        // Recherche dans la modale des personnes
        document.addEventListener('DOMContentLoaded', function() {
            const searchPersonsModal = document.getElementById('searchPersonsModal');
            const searchValidation = document.getElementById('contactsSearchValidation');
            if (searchPersonsModal) {
                searchPersonsModal.addEventListener('input', function(e) {
                    const rawSearch = (e.target.value || '').trim();
                    const isBelowMin = rawSearch.length > 0 && rawSearch.length < CONTACT_SEARCH_MIN_LENGTH;
                    contactsSearchTerm = rawSearch.length >= CONTACT_SEARCH_MIN_LENGTH ? rawSearch : '';
                    contactsCurrentPage = 1;

                    if (searchValidation) {
                        searchValidation.style.display = isBelowMin ? 'block' : 'none';
                    }

                    if (contactsSearchTimeout) {
                        clearTimeout(contactsSearchTimeout);
                    }

                    if (isBelowMin) {
                        return;
                    }

                    contactsSearchTimeout = setTimeout(() => {
                        loadGHLContacts(false, 1);
                    }, 600);
                });
            }
        });

        function selectContact(contactId) {
            // Désélectionner tous les contacts
            document.querySelectorAll('.contact-item').forEach(item => {
                item.classList.remove('selected');
            });

            // Sélectionner le contact cliqué
            const contactElement = document.querySelector(`[data-contact-id="${contactId}"]`);
            if (contactElement) {
                contactElement.classList.add('selected');
            }

            // Trouver le contact dans la liste
            selectedContact = allContacts.find(c => c.id === contactId);

            // Activer le bouton confirmer
            document.getElementById('confirmAddPerson').disabled = false;
        }

        async function confirmAddPerson() {
            if (!selectedContact) {
                alert('Veuillez sélectionner un contact');
                return;
            }

            // Désactiver le bouton pendant l'ajout
            const confirmButton = document.getElementById('confirmAddPerson');
            confirmButton.disabled = true;
            confirmButton.textContent = 'Ajout en cours...';

            try {
                // Sauvegarder dans la base de données via AJAX
                const response = await fetch('/api/properties/persons', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        id_location: idLocation,
                        property_listing_id: propertyListingId,
                        contact_id: selectedContact.id,
                        name: selectedContact.name,
                        email: selectedContact.email || '',
                        phone: selectedContact.phone || '',
                        implication: ''
                    })
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Erreur lors de l\'ajout');
                }

                // Vérifier si l'état vide existe
                const emptyState = document.querySelector('#persons-tab .empty-state');
                
                if (emptyState) {
                    // Créer le tableau s'il n'existe pas
                    const table = document.createElement('table');
                    table.id = 'personsTable';
                    table.innerHTML = `
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Implication</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    `;
                    emptyState.parentElement.replaceChild(table, emptyState);
                }

                // Ajouter la nouvelle ligne avec l'ID de la base de données
                const newRow = document.createElement('tr');
                newRow.dataset.personId = result.person.id;
                newRow.innerHTML = `
                    <td>${selectedContact.name}</td>
                    <td>${selectedContact.email || '-'}</td>
                    <td>${selectedContact.phone || '-'}</td>
                    <td>
                        ${createRoleSelector('', result.person.id)}
                    </td>
                    <td class="action-menu">
                        <button class="action-button" onclick="toggleDropdown(event, ${result.person.id}, 'person')">⋮</button>
                        <div class="dropdown-menu" id="dropdown-person-${result.person.id}">
                            <button class="dropdown-item" onclick="viewPerson(event, ${result.person.id})">
                                <svg viewBox="0 0 24 24"><path d="M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z"/></svg>
                                Voir
                            </button>
                            <button class="dropdown-item delete" onclick="confirmRemovePerson(event, ${result.person.id})">
                                <svg viewBox="0 0 24 24"><path d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z"/></svg>
                                Supprimer
                            </button>
                        </div>
                    </td>
                `;

                const tableBody = document.querySelector('#personsTable tbody');
                tableBody.appendChild(newRow);

                // Ajouter au tableau currentPersons
                currentPersons.push({
                    id: result.person.id,
                    contactId: selectedContact.id,
                    name: selectedContact.name,
                    email: selectedContact.email,
                    phone: selectedContact.phone,
                    role: ''
                });

                // Fermer la modale
                closeAddPersonModal();

                // Message de succès
                console.log('Personne ajoutée avec succès!');

            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'ajout de la personne: ' + error.message);
                confirmButton.disabled = false;
                confirmButton.textContent = 'Ajouter';
            }
        }

        async function removePerson(event, personId) {
            event.stopPropagation();
            
            // Fermer le dropdown
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });

            try {
                // Supprimer de la base de données via AJAX
                const response = await fetch(`/api/properties/persons/${personId}?id_location=${idLocation}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Erreur lors de la suppression');
                }

                // Trouver la ligne à supprimer
                const row = document.querySelector(`#personsTable tr[data-person-id="${personId}"]`);
                
                // Retirer du tableau currentPersons
                currentPersons = currentPersons.filter(p => p.id !== personId);
                
                // Retirer la ligne
                if (row) {
                    row.remove();
                }

                // Vérifier s'il reste des personnes
                const tbody = document.querySelector('#personsTable tbody');
                if (tbody && tbody.children.length === 0) {
                    // Réafficher l'état vide
                    const table = document.getElementById('personsTable');
                    const emptyState = document.createElement('div');
                    emptyState.className = 'empty-state';
                    emptyState.innerHTML = `
                        <svg viewBox="0 0 24 24">
                            <path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/>
                        </svg>
                        <h3>Aucune personne impliquée</h3>
                        <p>Cliquez sur "Ajouter" pour associer une personne à cette propriété</p>
                    `;
                    table.parentElement.replaceChild(emptyState, table);
                }

                console.log('Personne supprimée avec succès');

            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de la suppression: ' + error.message);
            }
        }

        // Fonction "Voir" pour les personnes
        function viewPerson(event, personId) {
            event.stopPropagation();
            
            // Fermer le dropdown
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
            
            // Trouver la personne pour obtenir son contact_id
            const person = currentPersons.find(p => p.id === personId);
            const contactId = person?.contact_id || person?.contactId;
            if (person && contactId) {
                // Ouvrir le contact dans GHL dans une nouvelle page
                const url = `https://go.optimocrm.com/v2/location/${idLocation}/contacts/detail/${contactId}`;
                window.open(url, '_blank');
            } else {
                alert('ID du contact introuvable');
            }
        }

        // Fonction pour confirmer la suppression d'une personne
        function confirmRemovePerson(event, personId) {
            event.stopPropagation();
            
            // Fermer le dropdown
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
            
            const person = currentPersons.find(p => p.id === personId);
            const personName = person ? person.name : 'cette personne';
            
            openConfirmationModal(
                `Êtes-vous sûr de vouloir supprimer ${personName} de cette propriété ?`,
                () => removePerson(event, personId)
            );
        }

        async function removePerson(event, personId) {
            event.stopPropagation();
            
            // Fermer le dropdown
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });

            try {
                // Supprimer de la base de données via AJAX
                const response = await fetch(`/api/properties/persons/${personId}?id_location=${idLocation}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Erreur lors de la suppression');
                }

                // Trouver la ligne à supprimer
                const row = document.querySelector(`#personsTable tr[data-person-id="${personId}"]`);
                
                // Retirer du tableau currentPersons
                currentPersons = currentPersons.filter(p => p.id !== personId);
                
                // Retirer la ligne
                if (row) {
                    row.remove();
                }

                // Vérifier s'il reste des personnes
                const tbody = document.querySelector('#personsTable tbody');
                if (tbody && tbody.children.length === 0) {
                    // Réafficher l'état vide
                    const table = document.getElementById('personsTable');
                    const emptyState = document.createElement('div');
                    emptyState.className = 'empty-state';
                    emptyState.innerHTML = `
                        <svg viewBox="0 0 24 24">
                            <path d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/>
                        </svg>
                        <h3>Aucune personne impliquée</h3>
                        <p>Cliquez sur "Ajouter" pour associer une personne à cette propriété</p>
                    `;
                    table.parentElement.replaceChild(emptyState, table);
                }

                console.log('Personne supprimée avec succès');

            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de la suppression: ' + error.message);
            }
        }

        // Fermer la modale si on clique en dehors
        window.onclick = function(event) {
            const personModal = document.getElementById('addPersonModal');
            const opportunityModal = document.getElementById('addOpportunityModal');
            const confirmationModal = document.getElementById('confirmationModal');
            
            if (event.target === personModal) {
                closeAddPersonModal();
            }
            if (event.target === opportunityModal) {
                closeAddOpportunityModal();
            }
            if (event.target === confirmationModal) {
                closeConfirmationModal();
            }
        }

        // ========== OPPORTUNITÉS ==========

        async function openAddOpportunityModal() {
            const modal = document.getElementById('addOpportunityModal');
            modal.classList.add('show');
            
            // Charger les opportunités depuis l'API GHL (avec cache)
            await loadGHLOpportunities();
        }

        function closeAddOpportunityModal() {
            const modal = document.getElementById('addOpportunityModal');
            modal.classList.remove('show');
            selectedOpportunity = null;
            const confirmButton = document.getElementById('confirmAddOpportunity');
            confirmButton.disabled = true;
            confirmButton.textContent = 'Ajouter';
            // Réinitialiser la recherche
            document.getElementById('searchOpportunitiesModal').value = '';
        }

        async function refreshOpportunitiesList() {
            opportunitiesCache = null;
            opportunitiesLastLoaded = 0;
            selectedOpportunity = null;
            const confirmButton = document.getElementById('confirmAddOpportunity');
            if (confirmButton) {
                confirmButton.disabled = true;
            }
            await loadGHLOpportunities(true);
        }

        async function loadGHLOpportunities(forceRefresh = false) {
            const container = document.getElementById('opportunityListContainer');
            
            // Vérifier si on a un cache valide
            const now = Date.now();
            if (!forceRefresh && opportunitiesCache && (now - opportunitiesLastLoaded) < CACHE_DURATION) {
                console.log('Utilisation du cache des opportunités');
                // Filtrer les opportunités qui ne sont pas déjà ajoutées
                const availableOpportunities = opportunitiesCache.filter(opp => {
                    return !currentOpportunities.some(co => (co.opportunity_id || co.opportunityId) === opp.id);
                });
                allOpportunities = availableOpportunities;
                displayOpportunities(allOpportunities);
                return;
            }

            container.innerHTML = '<div class="loading">Chargement des opportunités...</div>';

            try {
                // Appeler notre API Laravel
                const url = `{{ route("api.ghl.opportunities") }}?locationId={{ $locationId }}${forceRefresh ? '&force_refresh=1' : ''}`;
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || 'Erreur lors du chargement des opportunités');
                }

                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Erreur inconnue');
                }

                // Les opportunités viennent directement de la réponse
                const opportunitiesData = data.opportunities || [];

                // Mettre en cache
                opportunitiesCache = opportunitiesData;
                opportunitiesLastLoaded = Date.now();

                // Filtrer les opportunités qui ne sont pas déjà ajoutées
                const availableOpportunities = opportunitiesData.filter(opp => {
                    return !currentOpportunities.some(co => (co.opportunity_id || co.opportunityId) === opp.id);
                });

                allOpportunities = availableOpportunities;

                if (allOpportunities.length === 0) {
                    container.innerHTML = '<div class="empty-state"><p>Aucune nouvelle opportunité disponible</p></div>';
                    return;
                }

                // Afficher la liste des opportunités
                displayOpportunities(allOpportunities);

            } catch (error) {
                console.error('Erreur:', error);
                container.innerHTML = '<div class="empty-state"><p style="color: #e31c23;">Erreur lors du chargement des opportunités</p></div>';
            }
        }

        function displayOpportunities(opportunities) {
            const container = document.getElementById('opportunityListContainer');
            const countElement = document.getElementById('opportunitiesFoundCount');
            const count = opportunities.length;

            if (countElement) {
                countElement.textContent = `${count} opportunité${count > 1 ? 's' : ''} trouvée${count > 1 ? 's' : ''}`;
            }
            
            if (opportunities.length === 0) {
                container.innerHTML = '<div class="empty-state"><p>Aucune opportunité trouvée</p></div>';
                return;
            }

            container.innerHTML = '<div class="contact-list">' + 
                opportunities.map(opp => `
                    <div class="contact-item" data-opportunity-id="${opp.id}" onclick="selectOpportunity('${opp.id}')">
                        <div class="contact-name">${opp.name}</div>
                        <div class="contact-details">Statut: ${translateStatus(opp.status)}</div>
                    </div>
                `).join('') +
            '</div>';
        }

        // Recherche dans la modale des opportunités
        document.addEventListener('DOMContentLoaded', function() {
            const searchOpportunitiesModal = document.getElementById('searchOpportunitiesModal');
            if (searchOpportunitiesModal) {
                searchOpportunitiesModal.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase();
                    const filteredOpportunities = allOpportunities.filter(opp => 
                        opp.name.toLowerCase().includes(searchTerm) ||
                        (opp.status && opp.status.toLowerCase().includes(searchTerm)) ||
                        (opp.source && opp.source.toLowerCase().includes(searchTerm))
                    );
                    displayOpportunities(filteredOpportunities);
                });
            }
        });

        function selectOpportunity(opportunityId) {
            // Désélectionner toutes les opportunités
            document.querySelectorAll('[data-opportunity-id]').forEach(item => {
                item.classList.remove('selected');
            });

            // Sélectionner l'opportunité cliquée
            const oppElement = document.querySelector(`[data-opportunity-id="${opportunityId}"]`);
            if (oppElement) {
                oppElement.classList.add('selected');
            }

            // Trouver l'opportunité dans la liste
            selectedOpportunity = allOpportunities.find(o => o.id === opportunityId);

            // Activer le bouton confirmer
            document.getElementById('confirmAddOpportunity').disabled = false;
        }

        async function confirmAddOpportunity() {
            if (!selectedOpportunity) {
                alert('Veuillez sélectionner une opportunité');
                return;
            }

            // Désactiver le bouton pendant l'ajout
            const confirmButton = document.getElementById('confirmAddOpportunity');
            confirmButton.disabled = true;
            confirmButton.textContent = 'Ajout en cours...';

            try {
                // Sauvegarder dans la base de données via AJAX
                const response = await fetch('/api/properties/opportunities', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        id_location: idLocation,
                        property_listing_id: propertyListingId,
                        opportunity_id: selectedOpportunity.id,
                        name: selectedOpportunity.name,
                        pipeline_id: selectedOpportunity.pipelineId || '',
                        pipeline_stage_id: selectedOpportunity.pipelineStageId || '',
                        source: selectedOpportunity.source || '',
                        status: selectedOpportunity.status || '',
                        monetary_value: selectedOpportunity.monetaryValue || 0
                    })
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Erreur lors de l\'ajout');
                }

                // Vérifier si l'état vide existe
                const emptyState = document.querySelector('#opportunities-tab .empty-state');
                
                if (emptyState) {
                    // Créer le tableau s'il n'existe pas
                    const table = document.createElement('table');
                    table.id = 'opportunitiesTable';
                    table.innerHTML = `
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Pipeline</th>
                                <th>Étape</th>
                                <th>Source</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    `;
                    emptyState.parentElement.replaceChild(table, emptyState);
                }

                // Ajouter la nouvelle ligne avec l'ID de la base de données
                const newRow = document.createElement('tr');
                newRow.dataset.opportunityId = result.opportunity.id;
                newRow.innerHTML = `
                    <td>${selectedOpportunity.name}</td>
                    <td>${selectedOpportunity.pipelineId || '-'}</td>
                    <td>${selectedOpportunity.pipelineStageId || '-'}</td>
                    <td>${selectedOpportunity.source || '-'}</td>
                    <td class="action-menu">
                        <button class="action-button" onclick="toggleDropdown(event, ${result.opportunity.id}, 'opportunity')">⋮</button>
                        <div class="dropdown-menu" id="dropdown-opportunity-${result.opportunity.id}">
                            <button class="dropdown-item" onclick="viewOpportunity(event, ${result.opportunity.id})">
                                <svg viewBox="0 0 24 24"><path d="M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z"/></svg>
                                Voir
                            </button>
                            <button class="dropdown-item delete" onclick="confirmRemoveOpportunity(event, ${result.opportunity.id})">
                                <svg viewBox="0 0 24 24"><path d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z"/></svg>
                                Supprimer
                            </button>
                        </div>
                    </td>
                `;

                const tableBody = document.querySelector('#opportunitiesTable tbody');
                tableBody.appendChild(newRow);

                // Ajouter au tableau currentOpportunities
                currentOpportunities.push({
                    id: result.opportunity.id,
                    opportunityId: selectedOpportunity.id,
                    name: selectedOpportunity.name,
                    pipelineId: selectedOpportunity.pipelineId,
                    pipelineStageId: selectedOpportunity.pipelineStageId,
                    source: selectedOpportunity.source,
                    status: selectedOpportunity.status
                });

                // Fermer la modale
                closeAddOpportunityModal();

                // Message de succès
                console.log('Opportunité ajoutée avec succès!');

            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'ajout de l\'opportunité: ' + error.message);
                confirmButton.disabled = false;
                confirmButton.textContent = 'Ajouter';
            }
        }

        async function removeOpportunity(event, opportunityId) {
            event.stopPropagation();
            
            // Fermer le dropdown
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });

            try {
                // Supprimer de la base de données via AJAX
                const response = await fetch(`/api/properties/opportunities/${opportunityId}?id_location=${idLocation}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Erreur lors de la suppression');
                }

                // Trouver la ligne à supprimer
                const row = document.querySelector(`#opportunitiesTable tr[data-opportunity-id="${opportunityId}"]`);
                
                // Retirer du tableau currentOpportunities
                currentOpportunities = currentOpportunities.filter(o => o.id !== opportunityId);
                
                // Retirer la ligne
                if (row) {
                    row.remove();
                }

                // Vérifier s'il reste des opportunités
                const tbody = document.querySelector('#opportunitiesTable tbody');
                if (tbody && tbody.children.length === 0) {
                    // Réafficher l'état vide
                    const table = document.getElementById('opportunitiesTable');
                    const emptyState = document.createElement('div');
                    emptyState.className = 'empty-state';
                    emptyState.innerHTML = `
                        <svg viewBox="0 0 24 24">
                            <path d="M21,16.5C21,16.88 20.79,17.21 20.47,17.38L12.57,21.82C12.41,21.94 12.21,22 12,22C11.79,22 11.59,21.94 11.43,21.82L3.53,17.38C3.21,17.21 3,16.88 3,16.5V7.5C3,7.12 3.21,6.79 3.53,6.62L11.43,2.18C11.59,2.06 11.79,2 12,2C12.21,2 12.41,2.06 12.57,2.18L20.47,6.62C20.79,6.79 21,7.12 21,7.5V16.5M12,4.15L6.04,7.5L12,10.85L17.96,7.5L12,4.15M5,15.91L11,19.29V12.58L5,9.21V15.91M19,15.91V9.21L13,12.58V19.29L19,15.91Z"/>
                        </svg>
                        <h3>Aucune opportunité liée</h3>
                        <p>Cliquez sur "Ajouter" pour créer une opportunité pour cette propriété</p>
                    `;
                    table.parentElement.replaceChild(emptyState, table);
                }

                console.log('Opportunité supprimée avec succès');

            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de la suppression: ' + error.message);
            }
        }

        // Fonction "Voir" pour les opportunités
        function viewOpportunity(event, opportunityId) {
            event.stopPropagation();
            
            // Fermer le dropdown
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
            
            // Trouver l'opportunité pour obtenir son opportunity_id
            const opportunity = currentOpportunities.find(o => o.id === opportunityId);
            const oppId = opportunity?.opportunity_id || opportunity?.opportunityId;
            if (opportunity && oppId) {
                // Ouvrir l'opportunité dans GHL dans une nouvelle page
                const url = `https://go.optimocrm.com/v2/location/${idLocation}/opportunities/list/${oppId}`;
                window.open(url, '_blank');
            } else {
                alert('ID de l\'opportunité introuvable');
            }
        }

        // Fonction pour confirmer la suppression d'une opportunité
        function confirmRemoveOpportunity(event, opportunityId) {
            event.stopPropagation();
            
            // Fermer le dropdown
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });
            
            const opportunity = currentOpportunities.find(o => o.id === opportunityId);
            const opportunityName = opportunity ? opportunity.name : 'cette opportunité';
            
            openConfirmationModal(
                `Êtes-vous sûr de vouloir supprimer l'opportunité "${opportunityName}" ?`,
                () => removeOpportunity(event, opportunityId)
            );
        }

        async function removeOpportunity(event, opportunityId) {
            event.stopPropagation();
            
            // Fermer le dropdown
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.classList.remove('show');
            });

            try {
                // Supprimer de la base de données via AJAX
                const response = await fetch(`/api/properties/opportunities/${opportunityId}?id_location=${idLocation}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Erreur lors de la suppression');
                }

                // Trouver la ligne à supprimer
                const row = document.querySelector(`#opportunitiesTable tr[data-opportunity-id="${opportunityId}"]`);
                
                // Retirer du tableau currentOpportunities
                currentOpportunities = currentOpportunities.filter(o => o.id !== opportunityId);
                
                // Retirer la ligne
                if (row) {
                    row.remove();
                }

                // Vérifier s'il reste des opportunités
                const tbody = document.querySelector('#opportunitiesTable tbody');
                if (tbody && tbody.children.length === 0) {
                    // Réafficher l'état vide
                    const table = document.getElementById('opportunitiesTable');
                    const emptyState = document.createElement('div');
                    emptyState.className = 'empty-state';
                    emptyState.innerHTML = `
                        <svg viewBox="0 0 24 24">
                            <path d="M21,16.5C21,16.88 20.79,17.21 20.47,17.38L12.57,21.82C12.41,21.94 12.21,22 12,22C11.79,22 11.59,21.94 11.43,21.82L3.53,17.38C3.21,17.21 3,16.88 3,16.5V7.5C3,7.12 3.21,6.79 3.53,6.62L11.43,2.18C11.59,2.06 11.79,2 12,2C12.21,2 12.41,2.06 12.57,2.18L20.47,6.62C20.79,6.79 21,7.12 21,7.5V16.5M12,4.15L6.04,7.5L12,10.85L17.96,7.5L12,4.15M5,15.91L11,19.29V12.58L5,9.21V15.91M19,15.91V9.21L13,12.58V19.29L19,15.91Z"/>
                        </svg>
                        <h3>Aucune opportunité liée</h3>
                        <p>Cliquez sur "Ajouter" pour créer une opportunité pour cette propriété</p>
                    `;
                    table.parentElement.replaceChild(emptyState, table);
                }

                console.log('Opportunité supprimée avec succès');

            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de la suppression: ' + error.message);
            }
        }
    </script>
</body>
</html>
