#!/usr/bin/env python3

######
# This file exists because SonarCloud can't handle PHPUnit Dataproviders
######

import xml.etree.ElementTree as ET

et = ET.parse('tests_log.xml')
root = et.getroot()

for mastersuites in root:
    for suite in mastersuites:
        if not "file" in suite.attrib:
            continue
        filename = suite.attrib['file']
        for subsuite in suite:
            if subsuite.tag != "testsuite":
                continue
            if not "file" in subsuite.attrib:
                subsuite.attrib['file'] = filename

et.write('tests_log.xml')
