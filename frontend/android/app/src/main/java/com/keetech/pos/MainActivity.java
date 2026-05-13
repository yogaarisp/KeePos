package com.keetech.pos;

import android.Manifest;
import android.bluetooth.BluetoothAdapter;
import android.bluetooth.BluetoothDevice;
import android.bluetooth.BluetoothSocket;
import android.content.pm.PackageManager;
import android.os.Bundle;
import android.util.Base64;
import android.webkit.JavascriptInterface;
import androidx.core.app.ActivityCompat;
import com.getcapacitor.BridgeActivity;
import java.io.OutputStream;
import java.util.UUID;

public class MainActivity extends BridgeActivity {
    private static final UUID SPP_UUID = UUID.fromString("00001101-0000-1000-8000-00805f9b34fb");
    private BluetoothSocket socket;
    private OutputStream outputStream;
    private BluetoothDevice connectedDevice;

    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        // Register the bridge
        this.bridge.getWebView().addJavascriptInterface(new AndroidPrinterInterface(), "AndroidInterface");
    }

    public class AndroidPrinterInterface {
        @JavascriptInterface
        public void scanPrinters() {
            MainActivity.this.runOnUiThread(new Runnable() {
                @Override
                public void run() {
                    showDevicePicker();
                }
            });
        }

        private void showDevicePicker() {
            BluetoothAdapter adapter = BluetoothAdapter.getDefaultAdapter();
            if (adapter == null || !adapter.isEnabled()) {
                bridge.getWebView().evaluateJavascript("alert('Bluetooth mati atau tidak tersedia')", null);
                return;
            }

            if (ActivityCompat.checkSelfPermission(MainActivity.this,
                    Manifest.permission.BLUETOOTH_CONNECT) != PackageManager.PERMISSION_GRANTED) {
                ActivityCompat.requestPermissions(MainActivity.this,
                        new String[] { Manifest.permission.BLUETOOTH_CONNECT }, 1);
                return;
            }

            final java.util.List<BluetoothDevice> bondedDevices = new java.util.ArrayList<>(adapter.getBondedDevices());
            if (bondedDevices.isEmpty()) {
                bridge.getWebView().evaluateJavascript(
                        "alert('Tidak ada printer terpasang (paired). Silakan pasangkan printer di pengaturan Bluetooth HP dulu.')",
                        null);
                return;
            }

            final String[] deviceNames = new String[bondedDevices.size()];
            for (int i = 0; i < bondedDevices.size(); i++) {
                deviceNames[i] = bondedDevices.get(i).getName() + "\n" + bondedDevices.get(i).getAddress();
            }

            android.app.AlertDialog.Builder builder = new android.app.AlertDialog.Builder(MainActivity.this);
            builder.setTitle("Pilih Printer Bluetooth");
            builder.setItems(deviceNames, new android.content.DialogInterface.OnClickListener() {
                @Override
                public void onClick(android.content.DialogInterface dialog, int which) {
                    BluetoothDevice selected = bondedDevices.get(which);
                    connectToSpecificDevice(selected);
                }
            });
            builder.show();
        }

        private void connectToSpecificDevice(final BluetoothDevice device) {
            new Thread(new Runnable() {
                @Override
                public void run() {
                    try {
                        disconnect();
                        connectedDevice = device;
                        socket = device.createRfcommSocketToServiceRecord(SPP_UUID);
                        socket.connect();
                        outputStream = socket.getOutputStream();

                        // Notify JS success
                        MainActivity.this.runOnUiThread(new Runnable() {
                            @Override
                            public void run() {
                                bridge.getWebView().evaluateJavascript(
                                        "window.dispatchEvent(new CustomEvent('printer-connected', { detail: { name: '"
                                                + device.getName() + "' } }));",
                                        null);
                            }
                        });
                    } catch (Exception e) {
                        e.printStackTrace();
                        MainActivity.this.runOnUiThread(new Runnable() {
                            @Override
                            public void run() {
                                bridge.getWebView().evaluateJavascript(
                                        "alert('Gagal menyambung ke " + device.getName() + "')", null);
                            }
                        });
                    }
                }
            }).start();
        }

        @JavascriptInterface
        public boolean autoReconnect() {
            if (socket != null && socket.isConnected())
                return true;
            // For auto-reconnect, we can still try to find the last one or a default
            return false;
        }

        @JavascriptInterface
        public boolean disconnect() {
            try {
                if (outputStream != null) {
                    outputStream.close();
                    outputStream = null;
                }
                if (socket != null) {
                    socket.close();
                    socket = null;
                }
                connectedDevice = null;
                return true;
            } catch (Exception e) {
                return false;
            }
        }

        @JavascriptInterface
        public void setPrinterSettings(int dpi, int width, int charLimit) {
            // Store for future use if needed
        }

        @JavascriptInterface
        public void printRaw(String base64Data) {
            try {
                byte[] data = Base64.decode(base64Data, Base64.DEFAULT);
                if (outputStream != null && socket != null && socket.isConnected()) {
                    outputStream.write(data);
                    outputStream.flush();
                } else {
                    MainActivity.this.runOnUiThread(new Runnable() {
                        @Override
                        public void run() {
                            bridge.getWebView()
                                    .evaluateJavascript("alert('Printer terputus. Silakan hubungkan kembali.')", null);
                        }
                    });
                }
            } catch (Exception e) {
                e.printStackTrace();
                disconnect();
            }
        }
    }
}
