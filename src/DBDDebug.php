<?php
/**
 * DBD package
 *
 * MIT License
 *
 * Copyright (C) 2009-2017 by Nurlan Mukhanov <nurike@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

if(count($debug['queries'])) {
    ?>
  <style>
    /*Fun begins*/
    .DBD-Debug .tab_container {
      min-height: 30px;
      width: 90%;
      margin: 0 auto;
      font-family: 'Roboto', sans-serif;
      position: relative;
      background: #222222 url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAAcCAIAAADECPmYAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyZpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMDY3IDc5LjE1Nzc0NywgMjAxNS8wMy8zMC0yMzo0MDo0MiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKFdpbmRvd3MpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjBDRjc1MTdFNUU5NTExRTdCM0YyQTI5RERBOUQxODQxIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjBDRjc1MTdGNUU5NTExRTdCM0YyQTI5RERBOUQxODQxIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6MENGNzUxN0M1RTk1MTFFN0IzRjJBMjlEREE5RDE4NDEiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6MENGNzUxN0Q1RTk1MTFFN0IzRjJBMjlEREE5RDE4NDEiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz4l0gsjAAAAPUlEQVR42mLR1tZmYGBgYWJiAlGMjIwYFEgCg4dVkGQlWHks//79QxL8+fMniNLS0gJRb968QSiBygEEGAD5MBAOI2mDzQAAAABJRU5ErkJggg==') repeat-x top left;
      border-radius: 3px;
      border-top: 1px solid #464646;

      -webkit-box-shadow: 0 -2px 18px -1px rgba(0, 0, 0, 0.75);
      -moz-box-shadow: 0 -2px 18px -1px rgba(0, 0, 0, 0.75);
      box-shadow: 0 -2px 18px -1px rgba(0, 0, 0, 0.75);

    }

    .DBD-Debug input, section {
      clear: both;
      padding-top: 8px;
      display: none;
    }

    .DBD-Debug .labelTab {
      white-space: initial;
      min-width: 120px;
      display: block;
      float: left;
      height: 25px;

      color: rgba(134, 134, 134, 0.73);

      cursor: pointer;
      text-decoration: none;
      text-align: center;
      font-size: 12px;
      line-height: 1.90;
      background: #f0f0f0 url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAAZCAIAAACUxWgrAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyZpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMDY3IDc5LjE1Nzc0NywgMjAxNS8wMy8zMC0yMzo0MDo0MiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKFdpbmRvd3MpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjY0QURDQjcyNUU3RjExRTc4REZCRERERDEzMzVCM0MyIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjY0QURDQjczNUU3RjExRTc4REZCRERERDEzMzVCM0MyIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6NjRBRENCNzA1RTdGMTFFNzhERkJEREREMTMzNUIzQzIiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6NjRBRENCNzE1RTdGMTFFNzhERkJEREREMTMzNUIzQzIiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz7RlBPvAAAAMUlEQVR42mK0sLBgYGBgYWNjQ1BMDGBAPMXy//9/AoJYecQIfvz4EURpaWkBKYAAAwCXCBlQD999iwAAAABJRU5ErkJggg==') repeat-x top left;
    }

    .DBD-Debug .labelTab:hover {
      color: #6f6f6f;
      text-shadow: 1px 1px 1px rgba(27, 27, 27, 0.52)
    }

    .DBD-Debug .labelTab::before {
      content: '';
      background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAQAAAAZCAIAAACZ2xhsAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyZpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMDY3IDc5LjE1Nzc0NywgMjAxNS8wMy8zMC0yMzo0MDo0MiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKFdpbmRvd3MpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjY2Q0Q0RTZGNUU5NjExRTc4RTc4Rjg3QjZFRUQzNENFIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjY2Q0Q0RTcwNUU5NjExRTc4RTc4Rjg3QjZFRUQzNENFIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6NjZDRDRFNkQ1RTk2MTFFNzhFNzhGODdCNkVFRDM0Q0UiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6NjZDRDRFNkU1RTk2MTFFNzhFNzhGODdCNkVFRDM0Q0UiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz4sN/vMAAAAWklEQVR42mK0sLD48uULDw8PAwMDEy8vLxMTE5DDxsbGAsSMjIxAEiTDgARoxfn37x+qzH8YALoDVRlQDMgiUwYIiFWG7BxmSCB9/fr158+fLFpaWnAZgAADAIHTRpZaCo6fAAAAAElFTkSuQmCC') no-repeat left top;
      float: left;
      height: 25px;
      width: 4px;
    }

    .DBD-Debug .labelTab::after {
      content: '';

      background: no-repeat left top;
      float: right;
      height: 25px;
      width: 4px;
    }

    .DBD-Debug .labelTab:first-of-type {
      margin-left: 10px;
    }

    .DBD-Debug .labelTab:first-of-type::before {
      content: '';
      background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAQAAAAXCAIAAACj0XkcAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyZpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMDY3IDc5LjE1Nzc0NywgMjAxNS8wMy8zMC0yMzo0MDo0MiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKFdpbmRvd3MpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOkUxOTczRDY5NUU5NTExRTc4Qzc4QjRCMDhGMUY0MDNBIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOkUxOTczRDZBNUU5NTExRTc4Qzc4QjRCMDhGMUY0MDNBIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6RTE5NzNENjc1RTk1MTFFNzhDNzhCNEIwOEYxRjQwM0EiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6RTE5NzNENjg1RTk1MTFFNzhDNzhCNEIwOEYxRjQwM0EiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz5r3vOAAAAAU0lEQVR42pyNXQqAQAgG/WTxyZt4/8v54Ob+FLVFRAM+6AwIMwOgqkRUmHks7l7QEZE07Uw714VOvJgPWUTcTO3k9Sn7aZJpZnY8bVnOmg02AQYAthww9XYh//YAAAAASUVORK5CYII=') no-repeat left top;
      float: left;
      height: 25px;
      width: 4px;
    }

    .DBD-Debug .labelTab span {
      padding: 0 20px;
      /*
      -webkit-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
      */
    }

    .DBD-Debug #tab1:checked ~ #content1,
    .DBD-Debug #tab2:checked ~ #content2,
    .DBD-Debug #tab3:checked ~ #content3,
    .DBD-Debug #tab4:checked ~ #content4,
    .DBD-Debug #tab5:checked ~ #content5 {
      display: block;
      color: #999;
    }

    .DBD-Debug .tab_container [id^="tab"]:checked ~ .labelTab ~ .labelTab::before {
      background-image: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAMAAAAZCAIAAAB7BwMVAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyZpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMDY3IDc5LjE1Nzc0NywgMjAxNS8wMy8zMC0yMzo0MDo0MiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKFdpbmRvd3MpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOkRGQjE3MUU5NUU5NjExRTc4MjQyRDc0QTdGNEYwRUMyIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOkRGQjE3MUVBNUU5NjExRTc4MjQyRDc0QTdGNEYwRUMyIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6REZCMTcxRTc1RTk2MTFFNzgyNDJENzRBN0Y0RjBFQzIiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6REZCMTcxRTg1RTk2MTFFNzgyNDJENzRBN0Y0RjBFQzIiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz6BJ0f/AAAAMUlEQVR42mKxsLBgAAMWNjY2dBYTAwxQwmL5//8/abL4xciQ/fjxI5SlpaUFYQEEGAAUxBlTrfUS1gAAAABJRU5ErkJggg==');
    }

    .DBD-Debug .tab_container [id^="tab"]:checked ~ .labelTab ~ .labelTab ~ .labelTab::before,
    .DBD-Debug .tab_container [id^="tab"]:checked ~ .labelTab ~ .labelTab ~ .labelTab ~ .labelTab::before,
    .DBD-Debug .tab_container [id^="tab"]:checked ~ .labelTab ~ .labelTab ~ .labelTab ~ .labelTab ~ .labelTab::before {
      content: '';
      background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAQAAAAZCAIAAACZ2xhsAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyZpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMDY3IDc5LjE1Nzc0NywgMjAxNS8wMy8zMC0yMzo0MDo0MiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKFdpbmRvd3MpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOkNEMDg1NTMwNUU5MTExRTc4QzQ1OTA3OTUyNzBDMUMzIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOkNEMDg1NTMxNUU5MTExRTc4QzQ1OTA3OTUyNzBDMUMzIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6Q0QwODU1MkU1RTkxMTFFNzhDNDU5MDc5NTI3MEMxQzMiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6Q0QwODU1MkY1RTkxMTFFNzhDNDU5MDc5NTI3MEMxQzMiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz6gtRmTAAAAWklEQVR42mK0sLD48uULDw8PAwMDEy8vLxMTE5DDxsbGAsSMjIxAEiTDgARoxfn37x+qzH8YALoDVRlQDMgiUwYIiFWG7BxmSCB9/fr158+fLFpaWnAZgAADAIHTRpZaCo6fAAAAAElFTkSuQmCC') repeat-x left top;
      float: left;
      height: 25px;
      width: 4px;
    }

    /* active tab */
    .DBD-Debug .tab_container [id^="tab"]:checked + .labelTab:first-of-type {
      margin-left: 10px;
    }

    .DBD-Debug .tab_container [id^="tab"]:checked + .labelTab {
      background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAIAAAAZCAIAAACUxWgrAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyZpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMDY3IDc5LjE1Nzc0NywgMjAxNS8wMy8zMC0yMzo0MDo0MiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKFdpbmRvd3MpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjM1MzJDMUYzNUU3RDExRTc5NDRCRjNEMEI0MDI5NTg5IiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjM1MzJDMUY0NUU3RDExRTc5NDRCRjNEMEI0MDI5NTg5Ij4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6MzUzMkMxRjE1RTdEMTFFNzk0NEJGM0QwQjQwMjk1ODkiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6MzUzMkMxRjI1RTdEMTFFNzk0NEJGM0QwQjQwMjk1ODkiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz4GncYkAAAAKUlEQVR42mL08vJiYGBg4ebmBlJMDGDA8v//fwwePkEi5MjVjioIEGAAcrsxI3rn2dYAAAAASUVORK5CYII=') repeat-x left top;
      color: #dadada;
      text-shadow: 0 0 6px rgba(208, 208, 208, 0.7);
    }

    /* active tab */
    .DBD-Debug .tab_container [id^="tab"]:checked + .labelTab:first-of-type::before {
      content: '';
      background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAQAAAAZCAIAAACZ2xhsAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyZpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMDY3IDc5LjE1Nzc0NywgMjAxNS8wMy8zMC0yMzo0MDo0MiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKFdpbmRvd3MpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOkU5QkVGOUM5NUU3RDExRTc5MTFCREFCRjdDRjU0NDY3IiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOkU5QkVGOUNBNUU3RDExRTc5MTFCREFCRjdDRjU0NDY3Ij4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6RTlCRUY5Qzc1RTdEMTFFNzkxMUJEQUJGN0NGNTQ0NjciIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6RTlCRUY5Qzg1RTdEMTFFNzkxMUJEQUJGN0NGNTQ0NjciLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz4epC+KAAAAb0lEQVR42oxQuw7AIAhUAoOLTm5+AIn//1vODj5KbZvGxD5u4jg4LugYY2uNiEIICABqwHsPeuDgN+m9z4q68K78HJvurA3OOF/Wq50H6ylOrbWUgojyFpBKBpxz0t13jDHW2pwzMrPwlJLomwADAJEaQ+Ckj7/kAAAAAElFTkSuQmCC') no-repeat left top;
      float: left;
      height: 25px;
      width: 4px;
    }

    /* active tab */
    .DBD-Debug .tab_container [id^="tab"]:checked + .labelTab::before {
      content: '';

      background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAQAAAAZCAIAAACZ2xhsAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyZpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMDY3IDc5LjE1Nzc0NywgMjAxNS8wMy8zMC0yMzo0MDo0MiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKFdpbmRvd3MpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjBCOUVBQjhGNUU4MjExRTdCMUU0RUUxQUJGNjdFMzBGIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjBCOUVBQjkwNUU4MjExRTdCMUU0RUUxQUJGNjdFMzBGIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6MEI5RUFCOEQ1RTgyMTFFN0IxRTRFRTFBQkY2N0UzMEYiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6MEI5RUFCOEU1RTgyMTFFN0IxRTRFRTFBQkY2N0UzMEYiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz5oscj0AAAAdElEQVR42pyQMQ6AIAxFkTSMMLFxAI7A/Q/EDgTqFxEHxcE/NP39ryR0CyHUWlNKzjlSShERjLV2GNElxSVmvs2f5IFhsMbO8DATG0+/JN873LXAJilba6UU/BwN5ZxxHa01qoTB2BiDA5H3HnSMEZu7AAMAAtdwfu9lfIIAAAAASUVORK5CYII=') no-repeat left top;
      float: left;
      height: 25px;
      width: 4px;
    }

    /* active tab */
    .DBD-Debug .tab_container [id^="tab"]:checked + .labelTab::after {
      content: '';
      background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAQAAAAZCAIAAACZ2xhsAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyZpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMDY3IDc5LjE1Nzc0NywgMjAxNS8wMy8zMC0yMzo0MDo0MiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKFdpbmRvd3MpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjNFNEZCNUMxNUU3RTExRTdBRjQ4RkU0ODE3NzZBNTMzIiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjNFNEZCNUMyNUU3RTExRTdBRjQ4RkU0ODE3NzZBNTMzIj4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6M0U0RkI1QkY1RTdFMTFFN0FGNDhGRTQ4MTc3NkE1MzMiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6M0U0RkI1QzA1RTdFMTFFN0FGNDhGRTQ4MTc3NkE1MzMiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz7FHjdsAAAAdklEQVR42oyMuw3AIAwFHbCgAioGYB32H4EZQICAOD8lBYlyhWXr3fPivQ8hSCk558xaCwBKKSEEwg5tjBhjwAWDB3+THxots84x786tncnkwWfnRTsS7L0jYq1101prWusYYymF5ZyNMRTSsWkpJeccNVcBBgAkQ2phhqlYBQAAAABJRU5ErkJggg==') no-repeat left top;
      float: right;
      height: 25px;
      width: 4px;
    }

    .DBD-Debug .tab_container [id^="tab"]:checked + .labelTab:last-of-type::after {
      background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAQAAAAZCAIAAACZ2xhsAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyZpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMDY3IDc5LjE1Nzc0NywgMjAxNS8wMy8zMC0yMzo0MDo0MiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKFdpbmRvd3MpIiB4bXBNTTpJbnN0YW5jZUlEPSJ4bXAuaWlkOjgzNEQ2NTBDNUU5NzExRTc4MTk3RDYxQUU3MTU0MjE2IiB4bXBNTTpEb2N1bWVudElEPSJ4bXAuZGlkOjgzNEQ2NTBENUU5NzExRTc4MTk3RDYxQUU3MTU0MjE2Ij4gPHhtcE1NOkRlcml2ZWRGcm9tIHN0UmVmOmluc3RhbmNlSUQ9InhtcC5paWQ6ODM0RDY1MEE1RTk3MTFFNzgxOTdENjFBRTcxNTQyMTYiIHN0UmVmOmRvY3VtZW50SUQ9InhtcC5kaWQ6ODM0RDY1MEI1RTk3MTFFNzgxOTdENjFBRTcxNTQyMTYiLz4gPC9yZGY6RGVzY3JpcHRpb24+IDwvcmRmOlJERj4gPC94OnhtcG1ldGE+IDw/eHBhY2tldCBlbmQ9InIiPz7RkNprAAAAaklEQVR42oyQSwpAIQhFNRzXqAW0nfa/hPYQEX20HrwcBN2BIueqKMYYU0qtNQAw3ntJSwRLiMiRxhh/cRKzydadPNrUHj0ADt1tmjz2qEt770QSxcZ/sdZ+RSnFOcew1iqGnHMIgckUYAClDUOCtK5UzgAAAABJRU5ErkJggg==');
    }

    .DBD-Debug .button {
      float: right;
      color: #cecece;
      margin: 1px 6px 0 6px;
      -moz-box-shadow: inset 0 0 3px 2px rgba(0, 0, 0, 0.41);
      box-shadow: inset 0 0 3px 2px rgba(0, 0, 0, 0.41);
      border: 1px solid rgba(56, 56, 56, 0.73);
      border-radius: 10px;
      height: 20px;
      width: 20px;
      text-align: center;
      font-size: 12px;
      line-height: 1.6;
      /*
      cursor: pointer;
      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
      */
    }

    .DBD-Debug .button:hover {
      color: #ffffff;
    }

    .DBD-Debug .button span {
      -webkit-text-stroke: 1px rgba(0, 0, 0, 0.75);
      font-family: Arial, serif;

    }

    .aaaa {
      border-left: 1px solid #151515;
      border-right: 1px solid #151515;
      border-bottom: 1px solid rgba(27, 27, 27, 0.46);
      padding: 0 6px;
      background: #363636;
      position: relative;
    }

    .aaaa:first-child {
      -moz-border-radius-topleft: 3px;
      -moz-border-radius-topright: 3px;
      border-top-right-radius: 3px;
      border-top-left-radius: 3px;
      border-top: 1px solid #151515;
    }

    .aaaa:last-child {
      border-bottom: 1px solid #151515;
      -moz-border-radius-bottomleft: 3px;
      -moz-border-radius-bottomright: 3px;
      border-bottom-right-radius: 3px;
      border-bottom-left-radius: 3px;
    }

    .sql {
      font-weight: normal;
      font-family: monospace;
      color: #8e98d0;
      max-height: 18px;
      overflow: hidden;
      -moz-transition: 1s;
      -o-transition: 1s;
      -webkit-transition: 1s;
      transition: 1s;
    }

    .rowsTable {
      display: inline;
      line-height: 1.6;
      width: 100%;
    }

    .rowsTable td {
      vertical-align: text-bottom;
    }

    .informer {
      /*
      cursor: default;

      -moz-user-select: none;
      -ms-user-select: none;
      user-select: none;
      */
      float: right;
      margin-top: 2px;
      padding: 0 10px;
      line-height: 1.7;
      font-weight: normal;
      font-size: 11.5px;
      border-radius: 3px;
      -webkit-transform: skew(-20deg);
      -moz-transform: skew(20deg);
      -o-transform: skew(20deg);
      box-shadow: inset 0 0 3px 2px rgba(0, 0, 0, 0.41);
      border: 1px solid rgba(56, 56, 56, 0.73);
      color: #a9a9a9;
      text-shadow: 0 0 6px rgba(208, 208, 208, 0.7);
      margin-left: 6px;
    }

    .informer .ms {
      vertical-align: initial;
    }

    .labelSQL {
      white-space: initial;
    }

    .mark1 {
      color: #55ff00;
      font-weight: bold;
    }

    .mark2 {
      color: #aaff00;
      font-weight: bold;
    }

    .mark3 {
      color: #ffff00;
      font-weight: bold;
    }

    .mark4 {
      color: #ffaa00;
      font-weight: bold;
    }

    .mark5 {
      color: #ff5500;
      font-weight: bold;
    }

    .mark6 {
      color: #ff0000;
      font-weight: bold;
    }

    .cost {
      width: 80px;
      text-align: right;
      font-size: 12px;
      color: rgba(134, 134, 134, 0.73);
    }

    .path {
      font-weight: normal;
      font-style: italic;
      color: rgba(134, 134, 134, 0.73);
      font-size: 12px;
    }

    <?php

    foreach ($debug['queries'] as $name => $data) {
      $i=1;
      foreach ($data as $row) {
  ?>
    #sql-<?=$name?>-<?=$i?>:checked + label > div {
      white-space: pre;
      max-height: 999px;
      overflow: hidden;
    }

    <?php
    $i++;
    }
    }
   ?>

  </style>
  <div class="DBD-Debug" style="top: 0;position: absolute;">

    <div class="tab_container" id="DebugDBD">
      <div class="button"><span>▲</span></div>
        <?php
        $stat = array_reverse($debug['per_driver']);
        foreach($stat as $name => $row) {
            ?>
          <div class="informer">
              <?=$name?>: <span style="color: #00d8ff; font-weight: bold"><?=$row['total']?></span>, cost:
            <span class="mark<?=$row['mark']?>"><?=$row['cost']?></span>
            <small class="ms"> ms</small>
          </div>
            <?php
        }
        ?>

        <?php
        $i = 1;
        foreach($debug['per_driver'] as $name => $row) {
            ?>
          <input id="tab<?=$i?>" type="radio" name="tabs"<?=($i == 1 ? ' checked' : '')?>>
          <label class="labelTab" for="tab<?=$i?>"><span><?=$name?></span></label>
            <?php
            $i++;
        }
        $i = 1;
        foreach($debug['queries'] as $name => $data) {
            ?>
          <section id="content<?=$i?>" class="tab-content">

            <ol type="A" style="    margin: 0;
      color:#cccccc;
      counter-reset: my-badass-counter;
    padding: 0 5px 5px 25px;
    font-size: 12px;
    font-weight: bold;
    list-style: decimal;">
                <?php
                $l = 1;
                foreach($data as $row) {

                    ?>
                  <li class="aaaa">
                    <div>
                      <table border="0" cellpadding="2" cellspacing="0" class="rowsTable" width="100%">
                        <tr>
                          <td width="1000%">
                            <input type="checkbox" id="sql-<?=$name?>-<?=$l?>"><label class="labelSQL" for="sql-<?=$name?>-<?=$l?>">
                              <div class="sql"><?=$row['query']?></div>
                            </label>
                          </td>
                          <td nowrap class="path"><?=$row['caller']['file']?> : <?=$row['caller']['line']?></td>
                          <td nowrap class="cost"><span class="mark<?=$row['mark']?>"><?=$row['cost']?></span>
                            <small class="ms"> ms</small>
                          </td>
                        </tr>
                      </table>
                    </div>
                  </li>
                    <?php
                    $l++;
                }
                ?>
            </ol>
          </section>
            <?php
            $i++;
        }
        ?>

    </div>

  </div>
  <script>
      (function () {

          var cursors = "n w s e ne se sw nw".split(" ");

          function Resizable(elem, options) {
              options = options || {};
              options.max = options.max || [1E17, 1E17];
              options.min = options.min || [10, 10];
              options.allow = (options.allow || "11111111").split("");
              elem.addEventListener("mousemove", function (e) {
                  var dir = direction(this, e);
                  if (this.allow[dir] == "0") return;
                  this.style.cursor = dir == 8 ? "default" : cursors[dir] + "-resize";
              });
              elem.min = options.min;
              elem.max = options.max;
              elem.allow = options.allow;
              elem.pos = elem.getBoundingClientRect();
              elem.addEventListener("mousedown", resizeStart);
              document.body.onselectstart = function (e) {
                  return false
              };
          }

          function resizeStart(ev) {
              var dir = direction(this, ev);
              if (this.allow[dir] == "0") return;
              document.documentElement.style.cursor = this.style.cursor = cursors[dir] + "-resize";
              var pos = this.getBoundingClientRect();
              console.log(pos);
              console.log(this);
              var elem = this;
              var height = this.clientHeight;
              var width = this.clientWidth;
              document.addEventListener("mousemove", resize);

              function resize(e) {
                  if (dir == 0 || dir == 4 || dir == 7) {
                      if (e.clientY + elem.min[1] > ev.clientY + height) return;
                      var newTop = intVal(e.clientY) - intVal(ev.clientY) + intVal(pos.top);
                      var newHeight = intVal(height) + intVal(ev.clientY) - intVal(e.clientY);

                      //elem.style.top = newTop + 'px';
                      elem.style.height = newHeight + 'px';

                      console.log(elem.style.top);
                      console.log(elem.style.height);
                  }
                  if (dir == 1 || dir == 4 || dir == 5) {
                      elem.style.width = e.clientX - pos.left;
                  }
                  if (dir == 2 || dir == 5 || dir == 6) {
                      elem.style.height = e.clientY - pos.top;
                  }
                  if (dir == 3 || dir == 6 || dir == 7) {
                      if (e.clientX + elem.min[0] > ev.clientX + width) return;
                      elem.style.left = e.clientX - ev.clientX + pos.left;
                      elem.style.width = width + ev.clientX - e.clientX;
                  }
                  /*
                   if ( elem.clientHeight < elem.min[1] ) elem.style.height = elem.min[1];
                   if ( elem.clientWidth < elem.min[0] ) elem.style.width = elem.min[0];
                   if ( elem.clientHeight > elem.max[1] ) elem.style.height = elem.max[1];
                   if ( elem.clientWidth > elem.max[0] ) elem.style.width = elem.max[0];
                   if ( e.clientY < pos.bottom - elem.max[1] ) elem.style.top = pos.bottom - elem.max[1];
                   if ( e.clientX < pos.right - elem.max[0] ) elem.style.left = pos.right - elem.max[0];
                   */
              }

              document.addEventListener("mouseup", function () {
                  document.removeEventListener("mousemove", resize);
                  document.documentElement.style.cursor = elem.style.cursor = "default";
              });
          }

          function direction(elem, event, pad) {
              var res = 8;
              var pad = pad || 4;
              var pos = elem.getBoundingClientRect();
              var top = pos.top;
              var left = pos.left;
              var width = elem.clientWidth;
              var height = elem.clientHeight;
              var eTop = event.clientY;
              var eLeft = event.clientX;
              var isTop = eTop - top < pad;
              var isRight = left + width - eLeft < pad;
              var isBottom = top + height - eTop < pad;
              var isLeft = eLeft - left < pad;
              if (isTop) res = 0;
              if (isRight) res = 1;
              if (isBottom) res = 2;
              if (isLeft) res = 3;
              if (isTop && isRight) res = 4;
              if (isRight && isBottom) res = 5;
              if (isBottom && isLeft) res = 6;
              if (isLeft && isTop) res = 7;
              return res;
          }

          this.Resizable = Resizable;

      })(window, document);

      //Resizable(document.getElementById("DebugDBD"), {allow: "10100000", min: [250, 30]});

      //
  </script>
    <?php
}
?>