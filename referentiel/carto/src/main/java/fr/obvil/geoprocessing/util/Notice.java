package fr.obvil.geoprocessing.util;

import java.util.List;

import javax.xml.bind.annotation.XmlElement;
import javax.xml.bind.annotation.XmlElementWrapper;
import javax.xml.bind.annotation.XmlRootElement;

@XmlRootElement
public class Notice {

	private List<String> ref;
	
	private List<String> base;
	
	private List<String> lc;
	
	private List<String> sre;
	
	private List<String> mc;
	
	private List<String> cat;
	
	private List<String> tex;
	
	private List<String> sign;
	
	private List<String> dat;
	
	private List<String> scat;
	
	private List<String> cat_Obvil;

	public List<String> getRef() {
		return ref;
	}

	@XmlElementWrapper(name = "ref")
	@XmlElement(name = "e")
	public void setRef(List<String> ref) {
		this.ref = ref;
	}

	public List<String> getBase() {
		return base;
	}

	@XmlElementWrapper(name = "base")
	@XmlElement(name = "e")
	public void setBase(List<String> base) {
		this.base = base;
	}

	public List<String> getLc() {
		return lc;
	}

	@XmlElementWrapper(name = "lc")
	@XmlElement(name = "e")
	public void setLc(List<String> lc) {
		this.lc = lc;
	}

	public List<String> getSre() {
		return sre;
	}

	@XmlElementWrapper(name = "sre")
	@XmlElement(name = "e")
	public void setSre(List<String> sre) {
		this.sre = sre;
	}

	public List<String> getMc() {
		return mc;
	}

	@XmlElementWrapper(name = "mc")
	@XmlElement(name = "e")
	public void setMc(List<String> mc) {
		this.mc = mc;
	}

	public List<String> getCat() {
		return cat;
	}

	@XmlElementWrapper(name = "cat")
	@XmlElement(name = "e")
	public void setCat(List<String> cat) {
		this.cat = cat;
	}

	public List<String> getTex() {
		return tex;
	}

	@XmlElementWrapper(name = "tex")
	@XmlElement(name = "e")
	public void setTex(List<String> tex) {
		this.tex = tex;
	}

	public List<String> getSign() {
		return sign;
	}

	@XmlElementWrapper(name = "sign")
	@XmlElement(name = "e")
	public void setSign(List<String> sign) {
		this.sign = sign;
	}

	public List<String> getDat() {
		return dat;
	}

	@XmlElementWrapper(name = "dat")
	@XmlElement(name = "e")
	public void setDat(List<String> dat) {
		this.dat = dat;
	}

	public List<String> getScat() {
		return scat;
	}

	@XmlElementWrapper(name = "scat")
	@XmlElement(name = "e")
	public void setScat(List<String> scat) {
		this.scat = scat;
	}

	public List<String> getCat_Obvil() {
		return cat_Obvil;
	}

	@XmlElementWrapper(name = "cat_Obvil")
	@XmlElement(name = "e")
	public void setCat_Obvil(List<String> cat_Obvil) {
		this.cat_Obvil = cat_Obvil;
	}	
	
}
