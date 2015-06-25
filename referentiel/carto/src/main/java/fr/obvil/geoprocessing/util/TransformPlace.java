package fr.obvil.geoprocessing.util;

import java.io.BufferedReader;
import java.io.FileReader;
import java.io.FileWriter;
import java.io.IOException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.io.StringWriter;
import java.io.Writer;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;
import javax.xml.transform.OutputKeys;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;

import org.geonames.Toponym;
import org.w3c.dom.Document;
import org.w3c.dom.Element;

public class TransformPlace {

	public static void main(String[] args) {
		Map<String, Integer> p = createXMLTree(loadPlaceInformationFromThesaurus("ThesaurusLieux.txt"));
		/*
		 * List<String> l = new ArrayList<String>(); for (String name :
		 * p.keySet()) { l.add(name); } List<Toponym> res =
		 * GeolocalisePlace.loadGeoCoordinatesFromGeonames(l); for (Toponym top
		 * : res) { System.out.println(top.getName()); }
		 */
	}

	public static Map<String, List<TheasurusEntry>> loadPlaceInformationFromThesaurus(
			String filename) {
		Map<String, List<TheasurusEntry>> places = new LinkedHashMap<String, List<TheasurusEntry>>();
		try (BufferedReader br = new BufferedReader(new FileReader(filename))) {
			String sCurrentLine;
			int count = 0;
			String entryPlace = null;
			while ((sCurrentLine = br.readLine()) != null) {
				if (count > 0) {
					// System.out.println(sCurrentLine);
					List<TheasurusEntry> enL = new ArrayList<TheasurusEntry>();
					if (sCurrentLine
							.matches("^[A-Z|Â|Ê|Î|Ô|Û|À|Æ|Ç|É|È|Œ|Ù|Ä|Ë|Ï|Ö|Ü].*")) {
						places.put(sCurrentLine.trim(), enL);
						entryPlace = sCurrentLine.trim();
					} else {
						List<TheasurusEntry> p = places.get(entryPlace.trim());
						p.add(new TheasurusEntry(sCurrentLine.substring(4, 6)
								.trim(), sCurrentLine.substring(7).trim()));
					}
				}
				count++;
			}
			System.out.println(places.size());

		} catch (IOException e) {
			e.printStackTrace();
		}
		return places;
	}

	/*
	 * STERZING -- forme rejetée     EM VIPITERNO
	 * 
	 * VIPITERNO -- forme acceptée     TG ITALIE     EP STERZING
	 * 
	 * STERZING should not and it is not in the ouput
	 */

	public static Map<String, Integer> createXMLTree(
			Map<String, List<TheasurusEntry>> places) {
		try {
			Map<String, List<String>> tsMap = new HashMap<String, List<String>>();
			Map<String, List<String>> tgMap = new HashMap<String, List<String>>();
			Map<String, String> emMap = new HashMap<String, String>();
			Set<String> placeNames = places.keySet();
			for (String p : placeNames) {
				List<TheasurusEntry> l = places.get(p);
				for (TheasurusEntry te : l) {
					if (te.getEntryType().equals("TS")) {
						if (tsMap.get(p.trim()) == null) {
							List<String> list = new ArrayList<String>();
							list.add(te.getWord().trim());
							tsMap.put(p.trim(), list);
						} else {
							List<String> list = tsMap.get(p.trim());
							list.add(te.getWord().trim());
						}
					} else if (te.getEntryType().equals("TG")) {
						if (tgMap.get(p.trim()) == null) {
							List<String> list = new ArrayList<String>();
							list.add(te.getWord().trim());
							tgMap.put(p.trim(), list);
						} else {
							List<String> list = tgMap.get(p.trim());
							list.add(te.getWord().trim());
						}
					} else if (te.getEntryType().equals("EM")) {
						if (emMap.get(p.trim()) == null) {
							// List<String> list = new ArrayList<String>();
							// list.add(te.getWord().trim());
							emMap.put(p.trim(), te.getWord().trim());
						} /*
						 * else { there are no more than one rejected form by
						 * toponym ? List<String> list = emMap.get(p.trim());
						 * list.add(te.getWord().trim()); }
						 */
					}
				}
			}

			/*
			 * System.out.println("EM ---- keys: "+emMap.keySet().size());
			 * List<String> al = emMap.get("STERZING"); //keys contain rejected
			 * forms for (String a : al) System.out.println("EM: "+a);
			 * System.out.println("TG INVERS ----"); Set<String> at =
			 * tgMap.keySet(); for (String a : at) { List<String> atl =
			 * tgMap.get(a); if (atl.contains("PARIS")) { System.out.println(a);
			 * } } System.out.println("EP ----keys: "+epMap.keySet().size());
			 * List<String> al2 = tgMap.get("VIPITERNO"); for (String a : al2)
			 * System.out.println("EP: "+a);
			 */

			// Calculates level in tree
			Map<String, Integer> placesByLevel = new HashMap<String, Integer>();
			for (String p : placeNames) {
				List<String> tsL = tsMap.get(p);
				if (tsL == null) {
					// level 0 (leaf)
					placesByLevel.put(p, 0);
					// System.out.println("place level 0 (leaf): "+p);
				} else {
					Boolean hasLevel1 = false, hasLevel2 = false, hasLevel3 = false, hasLevel4 = false;
					// level 1
					int k = 0;
					Boolean keep = true;
					while (k < tsL.size() && keep) {
						List<String> tsL1 = tsMap.get(tsL.get(k));
						if (tsL1 != null) {
							keep = false;
						}
						k++;
					}
					if (keep) {
						placesByLevel.put(p, 1);
						hasLevel1 = true;
						// System.out.println("place level 1: "+p);
					}

					if (!hasLevel1) {
						// level 2
						k = 0;
						Boolean keep2 = true;
						while (k < tsL.size()) {
							List<String> tsL1 = tsMap.get(tsL.get(k));
							if (tsL1 != null) {
								int k1 = 0;
								while (k1 < tsL1.size() && keep2) {
									List<String> tsL2 = tsMap.get(tsL1.get(k1));
									if (tsL2 != null) {
										keep2 = false;
									}
									k1++;
								}
							}
							k++;
						}
						if (keep2) {
							placesByLevel.put(p, 2);
							hasLevel2 = true;
							// System.out.println("place level 2: "+p);
						}
					}
					if (!hasLevel2 && !hasLevel1) {
						// level 3
						k = 0;
						Boolean keep3 = true;
						while (k < tsL.size()) {
							List<String> tsL1 = tsMap.get(tsL.get(k));
							if (tsL1 != null) {
								int k1 = 0;
								while (k1 < tsL1.size()) {
									List<String> tsL2 = tsMap.get(tsL1.get(k1));
									if (tsL2 != null) {
										int k2 = 0;
										while (k2 < tsL2.size() && keep3) {
											List<String> tsL3 = tsMap.get(tsL2
													.get(k2));
											if (tsL3 != null) {
												keep3 = false;
											}
											k2++;
										}
									}
									k1++;
								}
							}
							k++;
						}
						if (keep3) {
							placesByLevel.put(p, 3);
							hasLevel3 = true;
							// System.out.println("place level 3: "+p);
						}
					}

					if (!hasLevel2 && !hasLevel1 && !hasLevel3) {
						// level 4
						k = 0;
						Boolean keep4 = true;
						while (k < tsL.size()) {
							List<String> tsL1 = tsMap.get(tsL.get(k));
							if (tsL1 != null) {
								int k1 = 0;
								while (k1 < tsL1.size()) {
									List<String> tsL2 = tsMap.get(tsL1.get(k1));
									if (tsL2 != null) {
										int k2 = 0;
										while (k2 < tsL2.size()) {
											List<String> tsL3 = tsMap.get(tsL2
													.get(k2));
											if (tsL3 != null) {
												int k3 = 0;
												// keep4 = true;
												while (k3 < tsL3.size()
														&& keep4) {
													List<String> tsL4 = tsMap
															.get(tsL3.get(k3));
													if (tsL4 != null) {
														keep4 = false;
													}
													k3++;
												}
											}
											k2++;
										}
									}
									k1++;
								}
							}
							k++;
						}
						if (keep4) {
							placesByLevel.put(p, 4);
							hasLevel4 = true;
							// System.out.println("place level 4: "+p);
						}
					}

					if (!hasLevel2 && !hasLevel1 && !hasLevel3 && !hasLevel4) {
						// System.out.println("place level greater then 4: "+p);
					}
				}
			}

			// System.out.println("places by level total: "+placesByLevel.size());
			writeXMLTree(placesByLevel, tsMap, tgMap, emMap);
			return placesByLevel;

		} catch (Exception e) {
			e.printStackTrace();
		}
		return null;
	}

	public static void writeXMLTree(Map<String, Integer> placesByLevel,
			Map<String, List<String>> tsMap, Map<String, List<String>> tgMap,
			Map<String, String> emMap) {

		try {
			Set<String> rejectedForms = emMap.keySet();

			// builds xml document
			DocumentBuilderFactory dbf = DocumentBuilderFactory.newInstance();
			DocumentBuilder builder = dbf.newDocumentBuilder();
			Document doc = builder.newDocument();
			Element root = doc.createElement("lieux");
			doc.appendChild(root);

			// printing level 4 branches
			Set<String> keysP = placesByLevel.keySet();
			for (String key : keysP) {
				if (placesByLevel.get(key) == 4) { // size of tree
					if (!rejectedForms.contains(key)) {
						Element placeElement = doc.createElement("lieu");
						root.appendChild(placeElement);
						Element placeNameElement = doc.createElement("nomLieu");
						placeElement.appendChild(placeNameElement);
						placeNameElement.appendChild(doc.createTextNode(key));

						// rejected form
						if (emMap.containsValue(key)) {
							// look for the key associated to this value
							for (String s : emMap.keySet()) {
								if (emMap.get(s).equals(key)) {
									Element placeNameElementRF = doc
											.createElement("formeRejetee");
									placeElement
											.appendChild(placeNameElementRF);
									placeNameElementRF.appendChild(doc
											.createTextNode(s));
								}
							}
						}

						List<String> children = tsMap.get(key);
						for (String a : children) {
							Element placeElement2 = doc.createElement("lieu");
							placeElement.appendChild(placeElement2);
							Element placeNameElement2 = doc
									.createElement("nomLieu");
							placeElement2.appendChild(placeNameElement2);
							placeNameElement2
									.appendChild(doc.createTextNode(a));
							
							// rejected form
							if (emMap.containsValue(a)) {
								// look for the key associated to this value
								for (String s : emMap.keySet()) {
									if (emMap.get(s).equals(a)) {
										Element placeNameElementRF = doc
												.createElement("formeRejetee");
										placeElement2
												.appendChild(placeNameElementRF);
										placeNameElementRF.appendChild(doc
												.createTextNode(s));
									}
								}
							}


							List<String> children2 = tsMap.get(a);
							if (children2 != null) {
								for (String b : children2) {
									Element placeElement3 = doc
											.createElement("lieu");
									placeElement2.appendChild(placeElement3);
									Element placeNameElement3 = doc
											.createElement("nomLieu");
									placeElement3
											.appendChild(placeNameElement3);
									placeNameElement3.appendChild(doc
											.createTextNode(b));

									// rejected form
									if (emMap.containsValue(b)) {
										// look for the key associated to this value
										for (String s : emMap.keySet()) {
											if (emMap.get(s).equals(b)) {
												Element placeNameElementRF = doc
														.createElement("formeRejetee");
												placeElement3
														.appendChild(placeNameElementRF);
												placeNameElementRF.appendChild(doc
														.createTextNode(s));
											}
										}
									}

									List<String> children3 = tsMap.get(b);
									if (children3 != null) {
										for (String c : children3) {
											Element placeElement4 = doc
													.createElement("lieu");
											placeElement3
													.appendChild(placeElement4);
											Element placeNameElement4 = doc
													.createElement("nomLieu");
											placeElement4
													.appendChild(placeNameElement4);
											placeNameElement4.appendChild(doc
													.createTextNode(c));

											// rejected form
											if (emMap.containsValue(c)) {
												// look for the key associated to this value
												for (String s : emMap.keySet()) {
													if (emMap.get(s).equals(c)) {
														Element placeNameElementRF = doc
																.createElement("formeRejetee");
														placeElement4
																.appendChild(placeNameElementRF);
														placeNameElementRF.appendChild(doc
																.createTextNode(s));
													}
												}
											}

											List<String> children4 = tsMap
													.get(c);
											if (children4 != null) {
												for (String d : children4) {
													Element placeElement5 = doc
															.createElement("lieu");
													placeElement4
															.appendChild(placeElement5);
													Element placeNameElement5 = doc
															.createElement("nomLieu");
													placeElement5
															.appendChild(placeNameElement5);
													placeNameElement5
															.appendChild(doc
																	.createTextNode(d));
													
													// rejected form
													if (emMap.containsValue(d)) {
														// look for the key associated to this value
														for (String s : emMap.keySet()) {
															if (emMap.get(s).equals(d)) {
																Element placeNameElementRF = doc
																		.createElement("formeRejetee");
																placeElement5
																		.appendChild(placeNameElementRF);
																placeNameElementRF.appendChild(doc
																		.createTextNode(s));
															}
														}
													}


												}
											}
										}
									}
								}
							}
						}
					}
				}
			}

			// printing level 3 branches, only those who have no parents
			keysP = placesByLevel.keySet();
			for (String key : keysP) {
				if (placesByLevel.get(key) == 3) {
					if (tgMap.get(key) == null && !rejectedForms.contains(key)) {
						Element placeElement = doc.createElement("lieu");
						root.appendChild(placeElement);
						Element placeNameElement = doc.createElement("nomLieu");
						placeElement.appendChild(placeNameElement);
						placeNameElement.appendChild(doc.createTextNode(key));

						// rejected form
						if (emMap.containsValue(key)) {
							// look for the key associated to this value
							for (String s : emMap.keySet()) {
								if (emMap.get(s).equals(key)) {
									Element placeNameElementRF = doc
											.createElement("formeRejetee");
									placeElement
											.appendChild(placeNameElementRF);
									placeNameElementRF.appendChild(doc
											.createTextNode(s));
								}
							}
						}
						List<String> children = tsMap.get(key);
						for (String a : children) {
							Element placeElement2 = doc.createElement("lieu");
							placeElement.appendChild(placeElement2);
							Element placeNameElement2 = doc
									.createElement("nomLieu");
							placeElement2.appendChild(placeNameElement2);
							placeNameElement2
									.appendChild(doc.createTextNode(a));

							// rejected form
							if (emMap.containsValue(a)) {
								// look for the key associated to this value
								for (String s : emMap.keySet()) {
									if (emMap.get(s).equals(a)) {
										Element placeNameElementRF = doc
												.createElement("formeRejetee");
										placeElement2
												.appendChild(placeNameElementRF);
										placeNameElementRF.appendChild(doc
												.createTextNode(s));
									}
								}
							}

							List<String> children2 = tsMap.get(a);
							if (children2 != null) {
								for (String b : children2) {
									Element placeElement3 = doc
											.createElement("lieu");
									placeElement2.appendChild(placeElement3);
									Element placeNameElement3 = doc
											.createElement("nomLieu");
									placeElement3
											.appendChild(placeNameElement3);
									placeNameElement3.appendChild(doc
											.createTextNode(b));

									// rejected form
									if (emMap.containsValue(b)) {
										// look for the key associated to this value
										for (String s : emMap.keySet()) {
											if (emMap.get(s).equals(b)) {
												Element placeNameElementRF = doc
														.createElement("formeRejetee");
												placeElement3
														.appendChild(placeNameElementRF);
												placeNameElementRF.appendChild(doc
														.createTextNode(s));
											}
										}
									}

									List<String> children3 = tsMap.get(b);
									if (children3 != null) {
										for (String c : children3) {
											Element placeElement4 = doc
													.createElement("lieu");
											placeElement3
													.appendChild(placeElement4);
											Element placeNameElement4 = doc
													.createElement("nomLieu");
											placeElement4
													.appendChild(placeNameElement4);
											placeNameElement4.appendChild(doc
													.createTextNode(c));
											
											// rejected form
											if (emMap.containsValue(c)) {
												// look for the key associated to this value
												for (String s : emMap.keySet()) {
													if (emMap.get(s).equals(c)) {
														Element placeNameElementRF = doc
																.createElement("formeRejetee");
														placeElement4
																.appendChild(placeNameElementRF);
														placeNameElementRF.appendChild(doc
																.createTextNode(s));
													}
												}
											}

										}
									}
								}
							}
						}
					} else {
						// System.out.println("places level 3 and level 4: "+key);
					}
				}
			}

			// printing ONLY level 2 branches
			keysP = placesByLevel.keySet();
			for (String key : keysP) {
				if (placesByLevel.get(key) == 2) {
					if (tgMap.get(key) == null && !rejectedForms.contains(key)) {
						Element placeElement = doc.createElement("lieu");
						root.appendChild(placeElement);
						Element placeNameElement = doc.createElement("nomLieu");
						placeElement.appendChild(placeNameElement);
						placeNameElement.appendChild(doc.createTextNode(key));

						// rejected form
						if (emMap.containsValue(key)) {
							// look for the key associated to this value
							for (String s : emMap.keySet()) {
								if (emMap.get(s).equals(key)) {
									Element placeNameElementRF = doc
											.createElement("formeRejetee");
									placeElement
											.appendChild(placeNameElementRF);
									placeNameElementRF.appendChild(doc
											.createTextNode(s));
								}
							}
						}

						List<String> children = tsMap.get(key);
						for (String a : children) {
							Element placeElement2 = doc.createElement("lieu");
							placeElement.appendChild(placeElement2);
							Element placeNameElement2 = doc
									.createElement("nomLieu");
							placeElement2.appendChild(placeNameElement2);
							placeNameElement2
									.appendChild(doc.createTextNode(a));

							// rejected form
							if (emMap.containsValue(a)) {
								// look for the key associated to this value
								for (String s : emMap.keySet()) {
									if (emMap.get(s).equals(a)) {
										Element placeNameElementRF = doc
												.createElement("formeRejetee");
										placeElement2
												.appendChild(placeNameElementRF);
										placeNameElementRF.appendChild(doc
												.createTextNode(s));
									}
								}
							}

							List<String> children2 = tsMap.get(a);
							if (children2 != null) {
								for (String b : children2) {
									Element placeElement3 = doc
											.createElement("lieu");
									placeElement2.appendChild(placeElement3);
									Element placeNameElement3 = doc
											.createElement("nomLieu");
									placeElement3
											.appendChild(placeNameElement3);
									placeNameElement3.appendChild(doc
											.createTextNode(b));

									// rejected form
									if (emMap.containsValue(b)) {
										// look for the key associated to this value
										for (String s : emMap.keySet()) {
											if (emMap.get(s).equals(b)) {
												Element placeNameElementRF = doc
														.createElement("formeRejetee");
												placeElement3
														.appendChild(placeNameElementRF);
												placeNameElementRF.appendChild(doc
														.createTextNode(s));
											}
										}
									}

								}
							}
						}
					} else {
						// System.out.println("places level 2 and level 3: "+key);
					}
				}
			}

			// printing level 1 branches
			keysP = placesByLevel.keySet();
			for (String key : keysP) {
				if (placesByLevel.get(key) == 1) {
					if (tgMap.get(key) == null && !rejectedForms.contains(key)) {
						Element placeElement = doc.createElement("lieu");
						root.appendChild(placeElement);
						Element placeNameElement = doc.createElement("nomLieu");
						placeElement.appendChild(placeNameElement);
						placeNameElement.appendChild(doc.createTextNode(key));

						// rejected form
						if (emMap.containsValue(key)) {
							// look for the key associated to this value
							for (String s : emMap.keySet()) {
								if (emMap.get(s).equals(key)) {
									Element placeNameElementRF = doc
											.createElement("formeRejetee");
									placeElement
											.appendChild(placeNameElementRF);
									placeNameElementRF.appendChild(doc
											.createTextNode(s));
								}
							}
						}

						List<String> children = tsMap.get(key);
						for (String a : children) {
							Element placeElement2 = doc.createElement("lieu");
							placeElement.appendChild(placeElement2);
							Element placeNameElement2 = doc
									.createElement("nomLieu");
							placeElement2.appendChild(placeNameElement2);
							placeNameElement2
									.appendChild(doc.createTextNode(a));
							
							// rejected form
							if (emMap.containsValue(a)) {
								// look for the key associated to this value
								for (String s : emMap.keySet()) {
									if (emMap.get(s).equals(a)) {
										Element placeNameElementRF = doc
												.createElement("formeRejetee");
										placeElement2
												.appendChild(placeNameElementRF);
										placeNameElementRF.appendChild(doc
												.createTextNode(s));
									}
								}
							}


						}
					} else {
						// System.out.println("places level 1 and level 2: "+key);
					}
				}
			}

			// printing level 0 branches
			keysP = placesByLevel.keySet();
			for (String key : keysP) {
				if (placesByLevel.get(key) == 0) {
					if (tgMap.get(key) == null && !rejectedForms.contains(key)) {
						Element placeElement = doc.createElement("lieu");
						root.appendChild(placeElement);

						Element placeNameElement = doc.createElement("nomLieu");
						placeElement.appendChild(placeNameElement);
						placeNameElement.appendChild(doc.createTextNode(key));

						// rejected form
						if (emMap.containsValue(key)) {
							// look for the key associated to this value
							for (String s : emMap.keySet()) {
								if (emMap.get(s).equals(key)) {
									Element placeNameElementRF = doc
											.createElement("formeRejetee");
									placeElement
											.appendChild(placeNameElementRF);
									placeNameElementRF.appendChild(doc
											.createTextNode(s));
								}
							}
						}
					} else {
						// System.out.println("places level 0 and level 1: "+key);
					}
				}
			}
			prettyPrint(doc);
		} catch (ParserConfigurationException e) {
			e.printStackTrace();
		} catch (Exception e) {
			e.printStackTrace();
		}

	}

	public static final void prettyPrint(Document xml) throws Exception {
		Transformer tf = TransformerFactory.newInstance().newTransformer();
		tf.setOutputProperty(OutputKeys.ENCODING, "UTF-8");
		tf.setOutputProperty(OutputKeys.INDENT, "yes");
		Writer out = new StringWriter();
		tf.transform(new DOMSource(xml), new StreamResult(out));
		FileWriter fw = new FileWriter("out.xml");
		fw.write(out.toString());
		fw.close();
		// System.out.println(out.toString());
	}

}

class TheasurusEntry {
	String entryType;
	String word;

	public TheasurusEntry(String et, String w) {
		this.entryType = et;
		this.word = w;
	}

	public String getEntryType() {
		return entryType;
	}

	public void setEntryType(String entryType) {
		this.entryType = entryType;
	}

	public String getWord() {
		return word;
	}

	public void setWord(String word) {
		this.word = word;
	}
}
