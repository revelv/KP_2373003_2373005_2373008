
// customer/index //
<style>
        body {
            background-color: #0b132b;
            color: #f5f6fa;
        }
        .card {
            background-color: #1c2541;
            border: none;
        }
        .btn-warning {
            background-color: #fca311;
            border: none;
        }
        .btn-warning:hover {
            background-color: #ffb703;
        }
    </style>

// order/index //

<style>
        body {
            background-color: #121728;
            color: #fff;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }

        h2 {
            color: #F5A300;
            margin-bottom: 20px;
        }

        .container {
            width: 90%;
            margin: 40px auto;
            background-color: #1b2233;
            padding: 30px;
            border-radius: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #222b40;
            border-radius: 8px;
            overflow: hidden;
        }

        table thead {
            background-color: #0f1524;
        }

        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #2c3650;
        }

        table tr:hover {
            background-color: #2a3450;
        }

        th {
            color: #F5A300;
            font-weight: 600;
        }

        select, button {
            padding: 6px 10px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
        }

        select {
            background-color: #2c3650;
            color: #fff;
        }

        button {
            cursor: pointer;
        }

        .btn-primary {
            background-color: #F5A300;
            color: #000;
            font-weight: bold;
        }

        .btn-primary:hover {
            background-color: #ffb400;
        }

        .btn-info {
            background-color: #3c6ff7;
            color: #fff;
        }

        .btn-info:hover {
            background-color: #5c85ff;
        }

        .status-form {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .alert {
            background-color: #0f1524;
            border-left: 5px solid #F5A300;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
    </style>

// payment/index //

<style>
        body {
            background-color: #1c2541;
            color: #fff;
        }
        .card {
            border: 1px solid #2c3035;
        }
        .card-header {
            font-weight: bold;
        }
        input, button {
            border-radius: 8px !important;
        }
    </style>

// product/index //

<style>
        body {
            background-color: #0b132b;
            color: #fff;
        }
        table {
            background-color: #161b22;
            border-radius: 10px;
            overflow: hidden;
        }
        th {
            background-color: #21262d;
            color: #f1c40f;
        }
        td {
            vertical-align: middle !important;
        }
        .btn-warning {
            background-color: #f39c12;
            color: #000;
            font-weight: 600;
        }
        .btn-warning:hover {
            background-color: #e0a800;
        }
        .form-control, select {
    background-color: #2d333b;
    color: #fff; /* This styles the text the user types */
    border: none;
}

/* --- ADD THESE RULES FOR PLACEHOLDER TEXT --- */

/* Standard for most modern browsers */
.form-control::placeholder {
    color: #fff;
    opacity: 1; /* Ensures it's fully opaque if browser default reduces it */
}

/* For older Edge browsers */
.form-control::-ms-input-placeholder {
    color: #fff;
}

/* For older Internet Explorer 10/11 */
.form-control:-ms-input-placeholder {
    color: #fff;
}
/* ------------------------------------------- */

.form-control:focus, select:focus {
    background-color: #2d333b;
    color: #fff;
    box-shadow: 0 0 0 0.2rem rgba(255,193,7,.25);
}
        
        img.preview {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>

// product/create //

<style>
    body {
      background-color: #0b132b;
      color: #ffffff;
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      padding: 0;
    }

    h2 {
      background-color: #1c2541;
      padding: 16px 24px;
      margin: 0;
      color: #ffd60a;
    }

    form {
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .form-row {
      display: flex;
      gap: 16px;
      flex-wrap: wrap;
    }

    .form-group {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    label {
      margin-bottom: 6px;
      font-weight: 500;
    }

    input[type="text"],
    input[type="number"],
    select,
    textarea {
      background-color: #2b354e;
      border: none;
      border-radius: 6px;
      padding: 10px;
      color: #ffffff;
      font-size: 14px;
      width: 100%;
      box-sizing: border-box;
    }

    textarea {
      resize: vertical;
      min-height: 100px;
    }

    .btn-group {
      display: flex;
      gap: 12px;
      margin-top: 8px;
    }

    button,
    .btn-cancel {
      border: none;
      border-radius: 8px;
      padding: 12px 24px;
      cursor: pointer;
      font-weight: bold;
      transition: 0.2s;
      text-decoration: none;
      font-size: 14px;
    }

    .btn-save {
      background-color: #ffd60a;
      color: #000;
    }

    .btn-save:hover {
      background-color: #ffe45c;
    }

    .btn-cancel {
      background-color: #4b5563;
      color: #fff;
    }

    .btn-cancel:hover {
      background-color: #6b7280;
    }
  </style>

// product/edit // 

<style>
        body {
            background-color: #0b132b;
            color: #ffffff;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }

        h2 {
            background-color: #1c2541;
            padding: 16px 24px;
            margin: 0;
            color: #ffd60a;
        }

        form {
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .form-row {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 6px;
            font-weight: 500;
        }

        input[type="text"],
        input[type="number"],
        select,
        textarea {
            background-color: #2b354e;
            border: none;
            border-radius: 6px;
            padding: 10px;
            color: #ffffff;
            font-size: 14px;
            width: 100%;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 8px;
        }

        button {
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            cursor: pointer;
            font-weight: bold;
            transition: 0.2s;
        }

        .btn-save {
            background-color: #ffd60a;
            color: #000;
        }

        .btn-save:hover {
            background-color: #ffe45c;
        }

        .btn-cancel {
            background-color: #4b5563;
            color: #fff;
        }

        .btn-cancel:hover {
            background-color: #6b7280;
        }

        img.preview {
            margin-top: 8px;
            border-radius: 6px;
        }

        button {
    border: none;
    border-radius: 8px;
    padding: 12px 24px;
    cursor: pointer;
    font-weight: bold;
    transition: 0.2s;
}

.btn-save {
    background-color: #ffd60a;
    color: #000;
}

.btn-save:hover {
    background-color: #ffe45c;
}

.btn-cancel {
    background-color: #4b5563;
    color: #fff;
}

.btn-cancel:hover {
    background-color: #6b7280;
}

    </style>
