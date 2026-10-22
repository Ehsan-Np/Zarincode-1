"""Fail the scoped critical-services job below its measured line-coverage floor."""
import sys
import xml.etree.ElementTree as ET
metrics = ET.parse(sys.argv[1]).getroot().find('project/metrics')
if metrics is None:
    raise SystemExit('No measured coverage metrics found')
total = int(metrics.attrib['statements'])
covered = int(metrics.attrib['coveredstatements'])
score = 100 * covered / total if total else 0
print(f'Critical-services line coverage: {covered}/{total} ({score:.2f}%)')
raise SystemExit(0 if total > 0 and score >= float(sys.argv[2]) else 1)
