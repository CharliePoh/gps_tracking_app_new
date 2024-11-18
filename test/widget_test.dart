import 'package:flutter_test/flutter_test.dart';
import 'package:gps_tracking_app/main.dart'; // Ensure this is the correct path.

void main() {
  testWidgets('Start Tracking button activates tracking',
      (WidgetTester tester) async {
    // Build the app and trigger a frame.
    await tester.pumpWidget(MyApp());

    // Verify initial state (Start Tracking button exists and no "Tracking Active" text).
    expect(find.text('Start Tracking'), findsOneWidget);
    expect(find.text('Stop Tracking'), findsNothing);
    expect(find.text('Tracking Active'), findsNothing);

    // Tap the "Start Tracking" button.
    await tester.tap(find.text('Start Tracking'));
    await tester.pump();

    // Verify that tracking is activated.
    expect(find.text('Start Tracking'), findsNothing);
    expect(find.text('Stop Tracking'), findsOneWidget);
    expect(find.text('Tracking Active'), findsOneWidget);
  });
}
