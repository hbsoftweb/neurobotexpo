<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
    <div>
        <div class="header-wrapper">
            <div class="header-logo-wrapper" style="display: flex; justify-content: stretch;">
                <img alt="Header Logo" loading="lazy" width="942" height="150" decoding="async" data-nimg="1"
                    src="assets/images/Neurobot-Logo.svg" style="color: transparent;">
            </div>
            <div class="input-container">
                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 512 512"
                    class="search-icon" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M505 442.7L405.3 343c-4.5-4.5-10.6-7-17-7H372c27.6-35.3 44-79.7 44-128C416 93.1 322.9 0 208 0S0 93.1 0 208s93.1 208 208 208c48.3 0 92.7-16.4 128-44v16.3c0 6.4 2.5 12.5 7 17l99.7 99.7c9.4 9.4 24.6 9.4 33.9 0l28.3-28.3c9.4-9.4 9.4-24.6.1-34zM208 336c-70.7 0-128-57.2-128-128 0-70.7 57.2-128 128-128 70.7 0 128 57.2 128 128 0 70.7-57.2 128-128 128z">
                    </path>
                </svg>
                <input class="input-search" placeholder="Search..." type="text" value="">
            </div>
            <div class="export-icon-wrapper">
                <img alt="Download icon" loading="lazy" width="512" height="512" decoding="async" data-nimg="1"
                    class="export-icon" src="assets/images/download.svg" style="color: transparent;">
            </div>
            <div class="button-wrapper-logout">
                <button class="btn-logout" style="height: 100%">Log Out</button>
            </div>
        </div>
        <div class="inquiry-data">
            <table class="inquiry-data__table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Contact</th>
                        <th>Designation</th>
                        <th>Industry</th>
                        <th>Category</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="inquiry-data__row">
                        <td>
                            <img alt="User Selfie" loading="lazy" width="201" height="201" decoding="async"
                                data-nimg="1" class="inquiry-data__selfie" src="assets/images/scalaton.webp"
                                style="color: transparent;">
                        </td>
                        <td>Amery Randolph</td>
                        <td>Dominguez Duran Plc</td>
                        <td class="width240">+91 1234567891</td>
                        <td>Decision Maker</td>
                        <td class="width300">Automotive, </td>
                        <td>
                            <span class="category category-microscope">Microscope</span>, <span
                                class="category category-vision">Vision</span>
                        </td>
                    </tr>
                    <tr class="inquiry-data__row">
                        <td>
                            <img alt="User Selfie" loading="lazy" width="201" height="201" decoding="async"
                                data-nimg="1" class="inquiry-data__selfie" src="assets/images/scalaton.webp"
                                style="color: transparent;">
                        </td>
                        <td>Amery Randolph</td>
                        <td>Dominguez Duran Plc</td>
                        <td class="width240">+91 1234567891</td>
                        <td>Decision Maker</td>
                        <td class="width300">Automotive, </td>
                        <td>
                            <span class="category category-microscope">Microscope</span>, <span
                                class="category category-vision">Vision</span>
                        </td>
                    </tr>
                    <tr class="inquiry-data__row">
                        <td>
                            <img alt="User Selfie" loading="lazy" width="201" height="201" decoding="async"
                                data-nimg="1" class="inquiry-data__selfie" src="assets/images/scalaton.webp"
                                style="color: transparent;">
                        </td>
                        <td>Amery Randolph</td>
                        <td>Dominguez Duran Plc</td>
                        <td class="width240">+91 1234567891</td>
                        <td>Decision Maker</td>
                        <td class="width300">Automotive, </td>
                        <td>
                            <span class="category category-microscope">Microscope</span>, <span
                                class="category category-vision">Vision</span>
                        </td>
                    </tr>
                    <tr class="inquiry-data__row">
                        <td>
                            <img alt="User Selfie" loading="lazy" width="201" height="201" decoding="async"
                                data-nimg="1" class="inquiry-data__selfie" src="assets/images/scalaton.webp"
                                style="color: transparent;">
                        </td>
                        <td>Amery Randolph</td>
                        <td>Dominguez Duran Plc</td>
                        <td class="width240">+91 1234567891</td>
                        <td>Decision Maker</td>
                        <td class="width300">Automotive, </td>
                        <td>
                            <span class="category category-microscope">Microscope</span>, <span
                                class="category category-vision">Vision</span>
                        </td>
                    </tr>
                    <tr class="inquiry-data__accordion expanded">
                        <td colspan="6">
                            <div class="inquiry-data__accordion-content">
                                <div class="wrapper-left-expand">
                                    <div>
                                        <img alt="User Selfie" class="inquiry-data__selfie-big"
                                            src="assets/images/scalaton-square.webp">
                                    </div>
                                    <p class="profile-info">Bhavesh</p>
                                    <p class="profile-info">Neurobot </p>
                                    <p class="profile-info">Founder/CEO</p>
                                </div>
                                <div class="wrapper-right-expand"
                                    style="display: flex;flex-direction: column;justify-content: space-between;">
                                    <p class="">
                                        <b class="label-left-expand">Contact Number:</b>&nbsp;&nbsp;&nbsp;+91 9375009040
                                    </p>
                                    <p>

                                        <b class="label-left-expand">Industry:</b>&nbsp;&nbsp;&nbsp; Pharma, FMCG

                                    </p>



                                    <p>
                                        <b class="label-left-expand">Printer:</b>&nbsp;&nbsp;&nbsp;None
                                    </p>
                                    <p>
                                        <b class="label-left-expand">Microscope:</b>&nbsp;&nbsp;&nbsp;3D
                                        Microscope
                                    </p>
                                    <p>
                                        <b class="label-left-expand">Vision:</b>&nbsp;&nbsp;&nbsp;Lenia Lite
                                        4K (LINE SCAN)
                                    </p>



                                    <p class="">
                                        <b class="label-left-expand">Special Mention:</b>&nbsp;&nbsp;&nbsp;Na
                                    </p>
                                    <p class="">
                                        <b class="label-left-expand">Date:</b>&nbsp;&nbsp;&nbsp; 21 December 2024 -
                                        12:24 AM
                                    </p>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>